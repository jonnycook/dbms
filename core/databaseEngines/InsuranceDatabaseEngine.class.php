<?php

require_once(__DIR__.'/DatabaseEngine.class.php');

class InsuranceDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
	}

	public function ids($model, array $storageConfig) {
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
	}

	public function singleInsert() { return true; }

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		// return false;

		if ($model == 'User' && $relName == 'insurance') {
			$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($id)]);
			if ($user['patientId'] && $user['patientId'] != 'DUMMY') {
				$response = qs1Get('Patient/Insurance', ['patientID' => $user['patientId']]);
				foreach ($response as $i => $obj) {
					$addresses[] = [
						'id' => $id . '-' . $obj['InsuranceID'],
						'idNumber' => $obj['PolicyNumber'],
						'groupNumber' => $obj['GroupNumber'],
						'rxBin' => $obj['PricePlanBin'],
						'user' => $id,
					];
				}
				$value = $addresses;
				return true;
			}
			else if ($user['patientId'] == 'DUMMY') {
				$value = [
					// [
					// 	'id' => "$id-PERM",
					// 	'street1' => '10 WINDY POINT',
					// 	'city' => 'ROCK ISLAND',
					// 	'state' => 'IL',
					// 	'zip' => 61201,
					// 	'user' => $id,
					// 	'name' => 'ROSALIND FRANKLIN',
					// ]
				];
				return true;
			}
			else {
				return false;
			}
		}
		else if ($model == 'Insurance' && $relName == 'user') {
			$parts = explode('-', $id);
			$value = $parts[0];
			return true;
		}
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($changes['relationships']['user'])]);

		if ($user['patientId'] && $user['patientId'] != 'DUMMY') {
			$fields = [
				'PolicyNumber' => def($changes['attributes']['idNumber'], ''),
				'GroupNumber' => def($changes['attributes']['groupNumber'], ''),
				'PricePlanBin' => def($changes['attributes']['rxBin'], ''),
				'PatientID' => $user['patientId'],
			];

			$fieldsStr = [];

			foreach ($fields as $key => $value) {
				$fieldsStr[] = "$key=$value";
			}
			$fieldsStr = implode('&', $fieldsStr);

			$ch = curl_init('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/Insurance');
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
			$response = curl_exec($ch);
			$response = json_decode($response, true);

			return "{$changes['relationships']['user']}-$response[InsuranceID]";
		}
	}

	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
		list($userId, $addressId) = explode('-', $id);
		$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($userId)]);

		if ($user['patientId'] && $user['patientId'] != 'DUMMY') {
			foreach ((array)$changes['attributes'] as $key => $value) {
				switch ($key) {
					case 'idNumber': $fields['PolicyNumber'] = def($value, ''); break;
					case 'groupNumber': $fields['GroupNumber'] = def($value, ''); break;
					case 'rxBin': $fields['PricePlanBin'] = def($value, ''); break;
				}
			}
			$fieldsStr = '';

			$fields['InsuranceID'] = $addressId;
			$fields['PatientID'] = $user['patientId'];

			foreach ($fields as $key => $value) {
				$fieldsStr[] = "$key=$value";
			}
			$fieldsStr = implode('&', $fieldsStr);

			$ch = curl_init('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/Insurance');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
			$response = curl_exec($ch);
		}
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
	}

	public function truncate(array $schema, array $storageConfig, $model) {
	}
}
