<?php

require_once(__DIR__.'/DatabaseEngine.class.php');

class AddressesDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
	}

	public function ids($model, array $storageConfig) {
	}


	private function addresses($userId) {
		if (isset($this->addresses[$userId])) {
			return $this->addresses[$userId];
		}
		else {
			$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($userId)]);
			if ($user['patientId'] != 'DUMMY' && $user['patientId']) {
				$response = qs1Get('Patient/Addresses', ['patientID' => $user['patientId']]);
				foreach ($response as $i => $obj) {
					if ($obj['AddressID'] == 'PERM') {
						list($lastName, $firstName) = explode(', ',$obj['Name']);
						$name = "$firstName $lastName";
					}
					else {
						$name = $obj['Name'];
					}
					$addresses[] = [
						'id' => $userId . '-' . $obj['AddressID'],
						'street1' => $obj['Address'],
						'street2' => $obj['Address2'],
						'city' => $obj['City'],
						'state' => $obj['State'],
						'zip' => $obj['Zip'],
						'name' => $name,
						'user' => $userId,
					];
				}
			}
			else {
				$addresses = [];
			}
			return $this->addresses = $addresses;
		}
	}

	// private function data($id) {

	// 	$response = json_decode(file_get_contents('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/Addresses?patientID=' . $user['patientId']), true);
	// 	foreach ($response as $i => $obj) {
	// 		if ($obj['AddressID'] == 'PERM') {
	// 			list($lastName, $firstName) = explode(', ',$obj['Name']);
	// 			$name = "$firstName $lastName";
	// 		}
	// 		else {
	// 			$name = $obj['Name'];
	// 		}
	// 		$addresses[] = [
	// 			'id' => $id . '-' . $obj['AddressID'],
	// 			'street1' => $obj['Address'],
	// 			'street2' => $obj['Address2'],
	// 			'city' => $obj['City'],
	// 			'state' => $obj['State'],
	// 			'zip' => $obj['Zip'],
	// 			'name' => $name,
	// 			'user' => $id,
	// 		];
	// 	}
	// 	$value = $addresses;

	// }

	private function address($id) {
		list($userId, $addressId) = explode('-', $id);
		$addresses = $this->addresses($userId);
		foreach ($addresses as $address) {
			if ($address['id'] == $id) {
				return $address;
			}
		}
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
		if ($model == 'Address') {
			$address = $this->address($id);
			return $address[$attrName];
		}
	}

	public function singleInsert() { return true; }

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		if ($model == 'User' && $relName == 'addresses') {
			$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($id)]);
			if ($user['patientId'] && $user['patientId'] != 'DUMMY') {
				$addresses = $this->addresses($id);
				$value = $addresses;
				return true;
			}
			else if ($user['patientId'] == 'DUMMY') {
				$value = [
					[
						'id' => "$id-PERM",
						'street1' => '10 WINDY POINT',
						'city' => 'ROCK ISLAND',
						'state' => 'IL',
						'zip' => 61201,
						'user' => $id,
						'name' => 'ROSALIND FRANKLIN',
					]
				];
				return true;
			}
			else {
				return false;
			}
		}
		else if ($model == 'Address' && $relName == 'user') {
			$parts = explode('-', $id);
			$value = $parts[0];
			return true;
		}
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($changes['relationships']['user'])]);

		if ($user['patientId'] && $user['patientId'] != 'DUMMY') {
			$fields = [
				'Address' => def($changes['attributes']['street1'], 'Address'),
				'Address2' => def($changes['attributes']['street2'], ''),
				'City' => def($changes['attributes']['city'], 'City'),
				'State' => def($changes['attributes']['state'], 'SS'),
				'Zip' => def($changes['attributes']['zip'], '12345'),
				'Name' => def($changes['attributes']['name'], 'Name'),
				'PatientID' => $user['patientId'],
			];

			$fieldsStr = [];

			foreach ($fields as $key => $value) {
				$fieldsStr[] = "$key=$value";
			}
			$fieldsStr = implode('&', $fieldsStr);

			$ch = curl_init('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/Addresses');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
			$response = curl_exec($ch);
			$response = json_decode($response, true);

			return "{$changes['relationships']['user']}-$response[AddressID]";
		}
	}

	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
		list($userId, $addressId) = explode('-', $id);
		$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($userId)]);

		if ($user['patientId'] && $user['patientId'] != 'DUMMY') {
			foreach ((array)$changes['attributes'] as $key => $value) {
				switch ($key) {
					case 'street1': $fields['Address'] = def($value, 'Address'); break;
					case 'street2': $fields['Address2'] = def($value, ''); break;
					case 'city': $fields['City'] = def($value, 'SS'); break;
					case 'state': $fields['State'] = def($value, 'City'); break;
					case 'zip': $fields['Zip'] = def($value, '12345'); break;
					case 'name': $fields['Name'] = def($value, 'Name'); break;
				}
			}
			$fieldsStr = '';

			if ($addressId == 'PERM') {
				$fields['PatientID'] = $user['patientId'];

				unset($fields['Name']);

				foreach ($fields as $key => $value) {
					$fieldsStr[] = "$key=$value";
				}
				$fieldsStr = implode('&', $fieldsStr);

				$ch = curl_init('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/Profile?patientID='.$user['patientId']);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
				$response = curl_exec($ch);
			}
			else {
				$fields['AddressID'] = $addressId;
				$fields['PatientID'] = $user['patientId'];

				foreach ($fields as $key => $value) {
					$fieldsStr[] = "$key=$value";
				}
				$fieldsStr = implode('&', $fieldsStr);

				$ch = curl_init('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/Addresses');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
				$response = curl_exec($ch);
			}
		}
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
	}

	public function truncate(array $schema, array $storageConfig, $model) {
	}
}
