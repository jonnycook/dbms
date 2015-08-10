<?php

define('QS1', true);


function qs1Get($url) {
	$mongo = new MongoClient();
	$id = md5($url);
	$document = $mongo->divvydose->qs1Cache->findOne(array('_id' => $id));
	if ($document) {
		return $document['data'];
	}
	else {
		$data = json_decode(file_get_contents($url), true);
		$mongo->divvydose->qs1Cache->insert(array('_id' => $id, 'data' => $data));
		return $data;
	}
}

return array(
	'init' => function($client) {
		if ($client['params']['dev'] && false) {
			define('QS1_SERVER', 'sandbox.qs1api.com');
			define('QS1_PHARMACY', 'VendorTest');
		}
		else {
			define('QS1_SERVER', '52.27.135.117');
			define('QS1_PHARMACY', 'divvyDOSE');
		}
	},
	'databases' => array(
		'default' => 'mongodb',
		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'divvydose',
		),
		'supplements' => array(
			'type' => 'json',
			'file' => 'supplements.json',
			'attributeNameMapping' => array('underscores' => 'camelCase'),
		),
		'addresses' => array(
			'type' => 'addresses'
		),
		'medications' => array(
			'type' => 'medications'
		),
		'patients' => array(
			'type' => 'patients',
		),
		'paymentMethods' => array(
			'type' => 'paymentMethods',
			'db' => 'divvydose',
		)
	),
	'models' => array(
		'User' => array(
			'storage' => array(
				// 'distributeUpdate' => function($db, $id, $model, $oid) {
				// 	if ($model == 'User') {
				// 		return $db->subscribers($db->resolveRel('User', $id, array('caringFor.caredForUser', 'caregivers.caregiverUser')));
				// 	}
				// 	else if ($model != 'Caregiver') {
				// 		return $db->subscribers($db->resolveRel('User', $id, array('caregivers.caregiverUser')));
				// 	}
				// },

				'filter' => function(&$user) {
					if ($user['patientId'] == 'DUMMY') {
						$timezone = date_default_timezone_get();
						date_default_timezone_set(timezone_name_from_abbr('', -$user['timezone']*60, 0));
						$user['divvyPacks'] = array(
			        date('Y-m-d') => array(
			            "prescriptions" => array(
			                "06001164" => array(
			                    "name" => "ATORVOSTATIN 20 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ),
			                "0112358" => array(
			                    "name" => "LISINOPRIL 20 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ),
			                "8675309" => array(
			                    "name" => "ASPIRIN 81 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ),
			                "6022141" => array(
			                    "name" => "FISH OIL + DHA 500 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take three times daily by mouth"
			                ),
			                "1618033" => array(
			                    "name" => "MULTIVITAMIN", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ),
			                "7973010" => array(
			                    "name" => "PROBIOTIC", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ),
			                "3182008" => array(
			                    "name" => "LEVOTHYROXINE 125 MCG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ),
			                "6934889" => array(
			                    "name" => "OMEPRAZOLE 40 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily at bedtime"
			                ),
			            ), 
			            "packets" => array(
			                array(
			                    "time" => date('Y-m-d H:i:s', mktime(date('H') + 1, 0, 0, date('m'), date('d'), date('Y'))), 
			                    "doses" => array(
			                        array(
			                            "rxNumber" => "06001164", 
			                            "quantity" => "1.00"
			                        ),
			                        array(
			                            "rxNumber" => "0112358", 
			                            "quantity" => "1.00"
			                        ),
			                        array(
			                            "rxNumber" => "8675309", 
			                            "quantity" => "1.00"
			                        ),
			                        array(
			                            "rxNumber" => "6022141", 
			                            "quantity" => "1.00"
			                        ),
			                        array(
			                            "rxNumber" => "1618033", 
			                            "quantity" => "1.00"
			                        ),
			                        array(
			                            "rxNumber" => "7973010", 
			                            "quantity" => "1.00"
			                        ),
			                        array(
			                            "rxNumber" => "3182008", 
			                            "quantity" => "1.00"
			                        ),
			                        array(
			                            "rxNumber" => "6934889", 
			                            "quantity" => "1.00"
			                        ),
			                    )
			                ), 
			                array(
			                    "time" => date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') + 1, date('Y'))), 
			                    "doses" => array(
			                        array(
			                            "rxNumber" => "1", 
			                            "quantity" => "1.00"
			                        )
			                    )
			                )
			            )
			        )
		       	);
						date_default_timezone_set($timezone);
						// $user['lastShipment'] = date('Y-m-d H:i:s', mktime(date('H'), 0, 0, date('m'), 3, date('Y')));
						$user['ssn'] = '5555';
						return;
					}

					if ($user['divvyPacks']) {
						$rxProfile = qs1Get('http://' . QS1_SERVER . '/api/Patient/' . QS1_PHARMACY . '/RxProfile?patientID=' . $user['patientId']);
						foreach ($rxProfile as $rx) {
							if (strpos($rx['PrescriberName'], ', ')) {
								$parts = explode(', ', $rx['PrescriberName']);
								$prescriber = "$parts[1] $parts[0]";
							}
							else {
								$prescriber = $rx['PrescriberName'];
							}
							$rxData[$rx['RxNumber']] = array(
								'ndc' => substr($rx['DispensedDrugNDC'], 0, 9),
								'name' => $rx['DispensedDrugName'],
								'sig' => $rx['SIG'],
								'prescriber' => $prescriber,
							);
						}

						foreach ($user['divvyPacks'] as $beginDate => $divvyPack) {
							$rxs = array();
							foreach ($divvyPack as $dose) {
								if (!$rxs[$rxNumber = $dose['rxNumber']]) {
									$rxs[$rxNumber] = array(
										'prescriber' => $rxData[$rxNumber]['prescriber'],
										'name' => $rxData[$rxNumber]['name'],
										'sig' => $rxData[$rxNumber]['sig']
									);
								}
								if (!$packets[$dose['time']]) {
									$packets[$dose['time']]['time'] = $dose['time'];
								}
								$time = $dose['time'];
								unset($dose['time']);
								$dose['quantity'] = floatval($dose['quantity']);
								$packets[$time]['doses'][] = $dose;
							}

							$user['divvyPacks'][$beginDate] = array(
								'prescriptions' => $rxs,
								'packets' => array_values($packets),
							);
						}
					}
				}
			),
			'attributes' => array(
				'firstName' => array(
					'type' => 'string', 
					// 'storage' => array(
					// 	'db' => 'patients',
					// )
				),
				'lastName' => array('type' => 'string'),

				'sex' => array('type' => 'string'),
				'email' => array('type' => 'string'),
				'dateOfBirth' => array('type' => 'date'),
				'phoneNumber' => array('type' => 'string'),
				'ssn' => array(
					'storage' => defined('QS1') ? array(
						'db' => 'patients',
					) : null,
					'type' => 'string'
				),

				'timezone' => array('type' => 'int'),

				'lastShipment' => array('type' => 'date'),
				'lastShipmentTrackingUrl' => array('type' => 'string'),
				'nextShipment' => array('type' => 'date'),

				'outstandingBalance' => array('type' => 'float'),

				'lastPayment' => array('type' => 'float'),
				'lastPaymentTime' => array('type' => 'datetime'),

				'divvyPacks' => array('type' => 'object'),

				'picture' => array('type' => 'string'),
				'patientId' => array('type' => 'string'),

				'agreedToTerms' => array('type' => 'bool'),
			),
			'relationships' => array(
				'caregivers' => array(
					'model' => 'Caregiver',
					'type' => 'Many',
					'inverseRelationship' => 'caredForUser',
					// 'access' => 'owner',
//					 'owner' => true,
					// 'storage' => array(
					// 	'objectOptions' => array(
					// 		'propertyOptions' => array(
					// 			'caregiverUser' => array(
					// 				'getRelationships' => false
					// 			)
					// 		),
					// 	)
					// ),
				),
				'caringFor' => array(
					'model' => 'Caregiver',
					'type' => 'Many',
					'inverseRelationship' => 'caregiverUser',
//					'owner' => true

					// 'storage' => array(
					// 	'objectObjects' => array(
					// 		'propertyOptions' => array(
					// 			'caredForUser' => array(
					// 				'getRelationships' => false
					// 			)
					// 		),
					// 	)
					// ),

					// 'access' => 'owner',
				),
				'prescriptions' => array(
					'storage' => defined('QS1') ? array(
						'db' => 'medications'
					) : null,
					'type' => 'Many',
					'model' => 'Prescription',
					'inverseRelationship' => 'user',
				),
				'medicineLogEntries' => array(
					'model' => 'MedicineLogEntry',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				),
				'addresses' => array(
					'storage' => defined('QS1') ? array(
						'db' => 'addresses'
					) : null,
					'model' => 'Address',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				),
				'allergies' => array(
					'model' => 'Allergy',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				),
				'conditions' => array(
					'model' => 'Condition',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				),
				'paymentMethods' => array(
					'model' => 'PaymentMethod',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				),
				'insurance' => array(
					'model' => 'Insurance',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				),

				'payments' => array(
					'model' => 'Payment',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				),
				'bills' => array(
					'model' => 'Bill',
					'type' => 'Many',
					'inverseRelationship' => 'user',
				),

				'currentAddress' => array(
					'model' => 'Address',
					'type' => 'One',
				),
				'currentPaymentMethod' => array(
					'model' => 'PaymentMethod',
					'type' => 'One',
				),
			)
		),

		'Caregiver' => array(
			'attributes' => array(
				'notifiedWhenForgotten' => array('type' => 'bool'),
				'notifiedWhenTaken' => array('type' => 'bool'),
				'notifiedWhenOffline' => array('type' => 'bool'),
				'notificationDelay' => array('type' => 'interval'),
			),
			'relationships' => array(
				'caregiverUser' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'caringFor',
					'owner' => true,
				),

				'caredForUser' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'caregivers',
					'owner' => true,
				)
			)
		),

		'Prescription' => array(
			'storage' => defined('QS1') ? array(
				'primary' => 'medications'
			) : null,

			'attributes' => array(
				'packaging' => array('type' => 'string', 'values' => array('In A Packet', 'Separate Bottle')),
				'frequency' => array('type' => 'string'),
				'autoRefill' => array('type' => 'bool'),
				'days' => array('type' => 'string'),
				'quantity' => array('type' => 'string'),
				'startedAt' => array('type' => 'date'),

				'type' => array('type' => 'string'),

				'image' => array('type' => 'string'),

				'directions' => array('type' => 'string'),
				'name' => array('type' => 'string'),
				'prescriber' => array('type' => 'string'),
				'rxNumber' => array('type' => 'string'),
				'endDate' => array('type' => 'date'),
			),
			'relationships' => array(
				'user' => array(
					'type' => 'One',
					'model' => 'User',
					'inverseRelationship' => 'prescriptions',
					'owner' => true,
				),
			),
		),

		'MedicineLogEntry' => array(
			'attributes' => array(
				'timestamp' => array('type' => 'datetime'),
				'time' => array('type' => 'string'),
				'event' => array('type' => 'string'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'medicineLogEntries',
					'owner' => true,
				),
				'doses' => array(
					'model' => 'MedicineLogEntryDose',
					'type' => 'Many',
					'inverseRelationship' => 'medicineLogEntry'
				)
			)
		),

		'MedicineLogEntryDose' => array(
			'attributes' => array(
				'quantity' => array('type' => 'string'),
			),
			'relationships' => array(
				'prescription' => array(
					'model' => 'Prescription',
					'type' => 'One'
				),
				'medicineLogEntry' => array(
					'model' => 'MedicineLogEntry',
					'type' => 'One',
					'inverseRelationship' => 'doses',
					'owner' => true
				)
			)
		),

		'Address' => array(
			'storage' => defined('QS1') ? array(
				'primary' => 'addresses'
			) : null,
			'attributes' => array(
				'street1' => array('type' => 'string'),
				'street2' => array('type' => 'string'),
				'name' => array('type' => 'string'),
				'city' => array('type' => 'string'),
				'state' => array('type' => 'string'),
				'zip' => array('type' => 'string'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'addresses',
					'owner' => true
				)
			)
		),

		'Allergy' => array(
			'attributes' => array(
				'name' => array('type' => 'string'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'allergies',
					'owner' => true,
				)
			)
		),
		'Condition' => array(
			'attributes' => array(
				'name' => array('type' => 'string'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'conditions',
					'owner' => true,
				)
			)
		),

		'PaymentMethod' => array(
			'storage' => array(
				'primary' => 'paymentMethods',
			),
			'attributes' => array(
				'firstName' => array('type' => 'string'),
				'lastName' => array('type' => 'string'),
				'number' => array('type' => 'string'),
				'type' => array('type' => 'string'),
				'cvc' => array('type' => 'string'),
				'expMonth' => array('type' => 'string'),
				'expYear' => array('type' => 'string'),

				'street1' => array('type' => 'string'),
				'street2' => array('type' => 'string'),
				'zip' => array('type' => 'string'),
				'city' => array('type' => 'string'),
				'state' => array('type' => 'string'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'paymentMethods',
					'owner' => true,
				)
			)
		),

		'Insurance' => array(
			'attributes' => array(
				'idNumber' => array('type' => 'string'),
				'groupNumber' => array('type' => 'string'),
				'rxBin' => array('type' => 'string')
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'insurance',
					'owner' => true,
				)
			)
		),

		'Payment' => array(
			'attributes' => array(
				'timestamp' => array('type' => 'datetime'),
				'amount' => array('type' => 'float'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'payments',
					'owner' => true,
				),
				'paymentMethod' => array(
					'model' => 'PaymentMethod',
					'type' => 'One'
				)
			)
		),

		'Bill' => array(
			'attributes' => array(
				'timestamp' => array('type' => 'datetime'),
				'amount' => array('type' => 'float'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'bills',
					'owner' => true,
				)
			)
		),
	),
	'resources' => array(
		'user' => array(
			'root' => 'User',

			'models' => array(
				'MedicineLogEntryDose' => array(
					'references' => array('prescription'),
				),
				'Payment' => array(
					'references' => array('paymentMethod'),
				),
				'User' => array(
					'references' => array('currentAddress', 'currentPaymentMethod'),
					'edges' => array('caregivers', 'caringFor'),
				)
			),
			'nodes' => array(
				'caregivers.caregiverUser' => array(
					'edges' => false
				),
				'caringFor.caredForUser' => array(
					'edges' => array(
						'caregivers' => false,
						'caringFor' => false
					)
				)
			)
		)
	),
	'routes' => array(
		'/' => array(
			'type' => 'db'
		),

		'/u' => array(
			array(
				'type' => 'model',
				'params' => function($client) {
					return array(
						'model' => 'User',
						'id' => $client['userId']
					);
				}
			),
		),

		'/user' => array(
			'type' => 'resource',
			'resource' => 'user',
			'id' => function($client) {
				return $client['userId'];
			}
		),

		'/:model/:id' => array(
			'type' => 'model'
		),
	)
);






// 'Supplement' => array(
// 	'storage' => array(
// 		'primary' => 'supplements',
// 		'config' => array(
// 			'supplements' => array(
// 				'@*' => array(
// 					'strengths' => array(
// 						'@strengths' => array(
// 							'@*' => '@id'
// 						)
// 					),
// 					'*' => '@*',
// 					null
// 				)
// 			)
// 		)
// 	),
//
// 	'attributes' => array(
// 		'name' => array('type' => 'string'),
// 		'primaryName' => array('type' => 'string'),
// 		'image' => array('type' => 'string'),
// 	),
//
// 	'relationships' => array(
// 		'strengths' => array(
// 			'type' => 'Many',
// 			'model' => 'SupplementStrength',
// 			'inverseRelationship' => 'supplement',
// 		)
// 	)
// ),
//
// 'SupplementStrength' => array(
// 	'storage' => array(
// 		'primary' => 'supplements',
// 		'config' => array(
// 			'supplements' => array(
// 				'@*' => array(
// 					'supplement' => '@id',
// 					'@strengths' => array(
// 						'@*' => array(
// 							'*' => '@*',
// 							null
// 						)
// 					)
// 				)
// 			)
// 		)
// 	),
// 	'attributes' => array(
// 		'name' => array('type' => 'string'),
// 		'retailItemCost' => array('type' => 'float'),
// 		'doseType' => array('type' => 'string'),
// 		'containerType' => array('type' => 'string'),
// 		'unitSize' => array('type' => 'string'),
// 		'canBeSubdivided' => array('type' => 'bool'),
// 		'inPackets' => array('type' => 'bool')
// 	),
// 	'relationships' => array(
// 		'supplement' => array(
// 			'type' => 'One',
// 			'model' => 'Supplement',
// 			'inverseRelationship' => 'strengths'
// 		)
// 	)
// ),
