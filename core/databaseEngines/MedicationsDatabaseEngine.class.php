<?php

require_once(__DIR__.'/DatabaseEngine.class.php');



class MedicationsDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
	}

	public function ids($model, array $storageConfig) {
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
		if ($attrName == 'state') {
			$document = _mongoClient()->divvydose->Prescription->findOne(['_id' => $id]);
			return $document['state'];
		}
	}

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		if ($model == 'Prescription' && $relName == 'user') {
			list($userId, $rxNumber) = explode('-', $id);
			$value = $userId;
			return true;
		}
		else if ($model == 'User' && $relName == 'prescriptions') {
			$user = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($id)]);

			if ($user['patientId']) {
				if ($user['patientId'] == 'DUMMY') {
					$prescriptions = [
						['ATORVOSTATIN 20 MG', '06001164!', 'Take once daily by mouth', 'ATOR5DA1'],	
						['LISINOPRIL 20 MG', '0112358', 'Take once daily by mouth', 'LISI469A'],	
						['ASPIRIN 81 MG', '8675309', 'Take once daily by mouth'],
						['FISH OIL + DHA 500 MG', '6022141', 'Take three times daily by mouth'],	
						['MULTIVITAMIN', '1618033', 'Take once daily by mouth'],	
						['PROBIOTIC', '7973010', 'Take once daily by mouth'],	
						['LEVOTHYROXINE 125 MCG', '3182008', 'Take once daily by mouth', 'LEVO24A1'],	
						['OMEPRAZOLE 40 MG', '6934889', 'Take once daily at bedtime'],	
					];
					foreach ($prescriptions as $i => $p) {
						$addresses[] = [
							'id' => $id . '-' . $p[1],
							'name' => $p[0],
							'rxNumber' => $p[1],
							'prescriber' => 'Doctor Jones',
							'endDate' => date('Y-m-d'),
							'directions' => $p[2],
							'user' => $id,
							'type' => 'Packet',
							'image' => 'http://jonnycook.com/dd/images/' . ($i + 1) . '.png',
							'packaging' => 'In A Packet',
							'monographUrl' => "https://server.divvydose.com/app/v1/monograph.php?drug=$p[3]",
						];
					}
				}
				else {
					$response = json_decode(file_get_contents("http://" . QS1_SERVER . "/api/Patient/" . QS1_PHARMACY . "/RxProfile?patientID=$user[patientId]&ActiveScriptsOnly=true&IncludeShortTerm=true"), true);
					foreach ($response as $i => $obj) {
						$ndc = substr($obj['DispensedDrugNDC'], 0, 9);
						$addresses[] = [
							'id' => $id . '-' . $obj['RxNumber'],
							'name' => $obj['DispensedDrugName'],
							'rxNumber' => $obj['RxNumber'],
							'prescriber' => $obj['PrescriberName'],
							'endDate' => substr($obj['LastFillDate'], 0, 4) . '-' . substr($obj['LastFillDate'], 4, 2) . '-' . substr($obj['LastFillDate'], 6, 2),
							'directions' => $obj['SIG'],
							'user' => $id,
							'packaging' => 'In A Packet',
							'type' => 'Packet',
							'image' => "https://s3-us-west-2.amazonaws.com/divvydose/pills/$ndc.png",
							'monographUrl' => "https://server.divvydose.com/app/v1/monograph.php?drug=$obj[DispensedDrugID]",
						];
					}
				}
				$value = $addresses;
				return true;
			}
			else {
				return false;
			}
		}
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		$fields = [
			'Address' => def($changes['attributes']['street1'], 'Address'),
			'Address2' => def($changes['attributes']['street2'], ''),
			'City' => def($changes['attributes']['city'], 'City'),
			'State' => def($changes['attributes']['state'], 'SS'),
			'Zip' => def($changes['attributes']['zipCode'], '12345'),
			'Name' => def($changes['attributes']['name'], 'Name'),
			'PatientID' => 'DEGESA'
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

	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
		if ($state = $changes['attributes']['state']) {
			$document = mongoClient()->divvydose->Prescription->findOne(['_id' => $id]);
			if ($state != $document['state']) {
				list($userId, $rxNumber) = explode('-', $id);
				$userDocument = _mongoClient()->divvydose->User->findOne(['_id' => new MongoId($userId)]);
				_mongoClient()->divvydose->Prescription->update(['_id' => $id], ['$set' => ['state' => $state]], ['upsert' => true]);
				zendeskClient()->tickets()->create(array(
					'subject' => "Prescription",
					'comment' => "$rxNumber $state",
					'tags' => array('prescription-update'),
					'requester_id' => $userDocument['zendeskId'],
					'submitter_id' => $userDocument['zendeskId'],
				));
			}
		}

		// foreach ((array)$changes['attributes'] as $key => $value) {
		// 	switch ($key) {
		// 		case 'street1': $fields['Address'] = def($value, 'Address'); break;
		// 		case 'street2': $fields['Address2'] = def($value, ''); break;
		// 		case 'city': $fields['City'] = def($value, 'SS'); break;
		// 		case 'state': $fields['State'] = def($value, 'City'); break;
		// 		case 'zipCode': $fields['Zip'] = def($value, '12345'); break;
		// 		case 'name': $fields['Name'] = def($value, 'Name'); break;
		// 	}
		// }
		// $fieldsStr = '';

		// list(, $addressId) = explode('-', $id);
		// $fields['AddressID'] = $addressId;
		// $fields['PatientID'] = 'DEGESA';

		// foreach ($fields as $key => $value) {
		// 	$fieldsStr[] = "$key=$value";
		// }
		// $fieldsStr = implode('&', $fieldsStr);

		// // var_dump($fields);

		// $ch = curl_init('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/Addresses');
		// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
		// $response = curl_exec($ch);
		// echo $response;
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
	}

	public function truncate(array $schema, array $storageConfig, $model) {
	}
}
