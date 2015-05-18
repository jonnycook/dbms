<?php

require_once(__DIR__.'/DatabaseEngine.class.php');

function def($value, $default) {
	if (!$value) return $default;
	return $value;
}


class AddressesDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
	}

	public function ids($model, array $storageConfig) {
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
	}

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		return false;
		$response = json_decode(file_get_contents('http://sandbox.qs1api.com/api/Patient/VendorTest/Addresses?patientID=DEGESA'), true);
		foreach ($response as $i => $obj) {
			$addresses[] = array(
				'id' => $id . '-' . $obj['AddressID'],
				'street1' => $obj['Address'],
				'street2' => $obj['Address2'],
				'city' => $obj['City'],
				'state' => $obj['State'],
				'zipCode' => $obj['Zip'],
				'name' => $obj['Name'],
				'user' => $id,
			);
		}
		$value = $addresses;
		return true;
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		$fields = array(
			'Address' => def($changes['attributes']['street1'], 'Address'),
			'Address2' => def($changes['attributes']['street2'], ''),
			'City' => def($changes['attributes']['city'], 'City'),
			'State' => def($changes['attributes']['state'], 'SS'),
			'Zip' => def($changes['attributes']['zipCode'], '12345'),
			'Name' => def($changes['attributes']['name'], 'Name'),
			'PatientID' => 'DEGESA'
		);

		$fieldsStr = array();

		foreach ($fields as $key => $value) {
			$fieldsStr[] = "$key=$value";
		}
		$fieldsStr = implode('&', $fieldsStr);

		$ch = curl_init('http://sandbox.qs1api.com/api/Patient/VendorTest/Addresses');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
		$response = curl_exec($ch);
		$response = json_decode($response, true);
		// var_dump($changes);
		return "{$changes['relationships']['user']}-$response[AddressID]";
	}

	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
		foreach ((array)$changes['attributes'] as $key => $value) {
			switch ($key) {
				case 'street1': $fields['Address'] = def($value, 'Address'); break;
				case 'street2': $fields['Address2'] = def($value, ''); break;
				case 'city': $fields['City'] = def($value, 'SS'); break;
				case 'state': $fields['State'] = def($value, 'City'); break;
				case 'zipCode': $fields['Zip'] = def($value, '12345'); break;
				case 'name': $fields['Name'] = def($value, 'Name'); break;
			}
		}
		$fieldsStr = '';

		list(, $addressId) = explode('-', $id);
		$fields['AddressID'] = $addressId;
		$fields['PatientID'] = 'DEGESA';

		foreach ($fields as $key => $value) {
			$fieldsStr[] = "$key=$value";
		}
		$fieldsStr = implode('&', $fieldsStr);

		// var_dump($fields);

		$ch = curl_init('http://sandbox.qs1api.com/api/Patient/VendorTest/Addresses');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
		$response = curl_exec($ch);
		echo $response;
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
	}

	public function truncate(array $schema, array $storageConfig, $model) {
	}
}