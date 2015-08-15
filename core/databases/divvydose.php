<?php

define('QS1', true);

function currentShipment($facility) {
	$begin = mktime(0, 0, 0, 6, 1, 2015);
	$days = floor((time() - $begin) / (60*60*24));
	$shipment = floor(($days - ($facility - 1))/28);
	return $shipment;
}

function shipmentDate($facility, $shipment) {
	$begin = mktime(0, 0, 0, 6, 1, 2015);
	return date('Y-m-d H:i:s', $begin + ($shipment * 28 + ($facility - 1)) * (60*60*24));
}


function qs1Get($url) {
	$mongo = new MongoClient();
	$id = md5($url);
	$document = $mongo->divvydose->qs1Cache->findOne(['_id' => $id]);
	if ($document) {
		return $document['data'];
	}
	else {
		$data = json_decode(file_get_contents($url), true);
		$mongo->divvydose->qs1Cache->insert(['_id' => $id, 'data' => $data]);
		return $data;
	}
}

return [
	'init' => function($client) {
		if ($client['dev']) {
			define('QS1_SERVER', 'sandbox.qs1api.com');
			define('QS1_PHARMACY', 'VendorTest');
		}
		else {
			define('QS1_SERVER', '52.27.135.117');
			define('QS1_PHARMACY', 'divvyDOSE');
		}
	},
	'databases' => [
		'default' => 'mongodb',
		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'divvydose',
		],
		'supplements' => [
			'type' => 'json',
			'file' => 'supplements.json',
			'attributeNameMapping' => ['underscores' => 'camelCase'],
		],
		'addresses' => [
			'type' => 'addresses'
		],
		'insurance' => [
			'type' => 'insurance'
		],
		'medications' => [
			'type' => 'medications'
		],
		'patients' => [
			'type' => 'patients',
		],
		'paymentMethods' => [
			'type' => 'paymentMethods',
			'db' => 'divvydose',
		]
	],
	'models' => [
		'User' => [
			'storage' => [
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
						$user['demo'] = true;
						$timezone = date_default_timezone_get();
						date_default_timezone_set(timezone_name_from_abbr('', -$user['timezone']*60, 0));
						$user['divvyPacks'] = [
			        date('Y-m-d') => [
			            "prescriptions" => [
			                "06001164" => [
			                    "name" => "ATORVOSTATIN 20 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ],
			                "0112358" => [
			                    "name" => "LISINOPRIL 20 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ],
			                "8675309" => [
			                    "name" => "ASPIRIN 81 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ],
			                "6022141" => [
			                    "name" => "FISH OIL + DHA 500 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take three times daily by mouth"
			                ],
			                "1618033" => [
			                    "name" => "MULTIVITAMIN", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ],
			                "7973010" => [
			                    "name" => "PROBIOTIC", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ],
			                "3182008" => [
			                    "name" => "LEVOTHYROXINE 125 MCG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily by mouth"
			                ],
			                "6934889" => [
			                    "name" => "OMEPRAZOLE 40 MG", 
			                    "prescriber" => "Doctor Jones", 
			                    "sig" => "Take once daily at bedtime"
			                ],
			            ], 
			            "packets" => [
			                [
			                    "time" => date('Y-m-d H:i:s', mktime(date('H') + 1, 0, 0, date('m'), date('d'), date('Y'))), 
			                    "doses" => [
			                        [
			                            "rxNumber" => "06001164", 
			                            "quantity" => "1.00"
			                        ],
			                        [
			                            "rxNumber" => "0112358", 
			                            "quantity" => "1.00"
			                        ],
			                        [
			                            "rxNumber" => "8675309", 
			                            "quantity" => "1.00"
			                        ],
			                        [
			                            "rxNumber" => "6022141", 
			                            "quantity" => "1.00"
			                        ],
			                        [
			                            "rxNumber" => "1618033", 
			                            "quantity" => "1.00"
			                        ],
			                        [
			                            "rxNumber" => "7973010", 
			                            "quantity" => "1.00"
			                        ],
			                        [
			                            "rxNumber" => "3182008", 
			                            "quantity" => "1.00"
			                        ],
			                        [
			                            "rxNumber" => "6934889", 
			                            "quantity" => "1.00"
			                        ],
			                    ]
			                ], 
			                [
			                    "time" => date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') + 1, date('Y'))), 
			                    "doses" => [
			                        [
			                            "rxNumber" => "06001164", 
			                            "quantity" => "1.00"
			                        ]
			                    ]
			                ]
			            ]
			        ]
		       	];
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
							$rxData[$rx['RxNumber']] = [
								'ndc' => substr($rx['DispensedDrugNDC'], 0, 9),
								'name' => $rx['DispensedDrugName'],
								'sig' => $rx['SIG'],
								'prescriber' => $prescriber,
							];
						}

						foreach ($user['divvyPacks'] as $beginDate => $divvyPack) {
							$rxs = [];
							foreach ($divvyPack as $dose) {
								if (!$rxs[$rxNumber = $dose['rxNumber']]) {
									$rxs[$rxNumber] = [
										'prescriber' => $rxData[$rxNumber]['prescriber'],
										'name' => $rxData[$rxNumber]['name'],
										'sig' => $rxData[$rxNumber]['sig']
									];
								}
								if (!$packets[$dose['time']]) {
									$packets[$dose['time']]['time'] = $dose['time'];
								}
								$time = $dose['time'];
								unset($dose['time']);
								$dose['quantity'] = floatval($dose['quantity']);
								$packets[$time]['doses'][] = $dose;
							}

							$user['divvyPacks'][$beginDate] = [
								'prescriptions' => $rxs,
								'packets' => array_values($packets),
							];
						}
					}

					if ($user['facility']) {
						$facility = substr($user['facility'], 1);
						$currentShipment = currentShipment($facility);

						$user['lastShipment'] = shipmentDate($facility, $currentShipment);
						$user['nextShipment'] = shipmentDate($facility, $currentShipment + 1);
						// $currentDate = date('j');
						// if ($shipmentDate <= $currentDate) {
						// 	$user['lastShipment'] = date('Y') . '-' . date('m') . '-' . str_pad($shipmentDate, 2 - strlen($shipmentDate), '0', STR_PAD_LEFT);
						// 	$user['nextShipment'] = date('Y-m-d', mktime(0, 0, 0, date('n') + 1, $shipmentDate, date('Y')));
						// }
						// else {
						// 	$user['nextShipment'] = date('Y') . '-' . date('m') . '-' . str_pad($shipmentDate, 2 - strlen($shipmentDate), '0', STR_PAD_LEFT);
						// 	$user['lastShipment'] = date('Y-m-d', mktime(0, 0, 0, date('n') - 1, $shipmentDate, date('Y')));
						// }
					}
				}
			],
			'attributes' => [
				'firstName' => [
					'type' => 'string', 
					// 'storage' => array(
					// 	'db' => 'patients',
					// )
				],
				'lastName' => ['type' => 'string'],

				'sex' => ['type' => 'string'],
				'email' => ['type' => 'string'],
				'dateOfBirth' => ['type' => 'date'],
				'phoneNumber' => ['type' => 'string'],
				'ssn' => [
					'storage' => defined('QS1') ? [
						'db' => 'patients',
					] : null,
					'type' => 'string'
				],

				'facility' => [
					'storage' => defined('QS1') ? [
						'db' => 'patients',
					] : null,
					'type' => 'string'
				],

				'timezone' => ['type' => 'int'],

				'lastShipment' => ['type' => 'date'],
				'lastShipmentTrackingUrl' => ['type' => 'string'],
				'nextShipment' => ['type' => 'date'],

				'outstandingBalance' => ['type' => 'float'],

				'lastPayment' => ['type' => 'float'],
				'lastPaymentTime' => ['type' => 'datetime'],

				'divvyPacks' => ['type' => 'object'],

				'picture' => ['type' => 'string'],
				'patientId' => ['type' => 'string'],

				'agreedToTerms' => ['type' => 'bool'],

				'demo' => ['type' => 'bool'],
			],
			'relationships' => [
				'caregivers' => [
					'model' => 'Caregiver',
					'type' => 'Many',
					'inverseRelationship' => 'caredForUser',
				],
				'caringFor' => [
					'model' => 'Caregiver',
					'type' => 'Many',
					'inverseRelationship' => 'caregiverUser',
				],
				'prescriptions' => [
					'storage' => defined('QS1') ? [
						'db' => 'medications'
					] : null,
					'type' => 'Many',
					'model' => 'Prescription',
					'inverseRelationship' => 'user',
				],
				'medicineLogEntries' => [
					'model' => 'MedicineLogEntry',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				],
				'addresses' => [
					'storage' => defined('QS1') ? [
						'db' => 'addresses'
					] : null,
					'model' => 'Address',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				],
				'allergies' => [
					'model' => 'Allergy',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				],
				'conditions' => [
					'model' => 'Condition',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				],
				'paymentMethods' => [
					'model' => 'PaymentMethod',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				],
				'insurance' => [
					'storage' => defined('QS1') ? [
						'db' => 'insurance'
					] : null,
					'model' => 'Insurance',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				],

				'payments' => [
					'model' => 'Payment',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				],
				'bills' => [
					'model' => 'Bill',
					'type' => 'Many',
					'inverseRelationship' => 'user',
				],

				'currentAddress' => [
					'model' => 'Address',
					'type' => 'One',
				],
				'currentPaymentMethod' => [
					'model' => 'PaymentMethod',
					'type' => 'One',
				],
				'currentInsurance' => [
					'model' => 'Insurance',
					'type' => 'One',
				],
			]
		],

		'Caregiver' => [
			'attributes' => [
				'notifiedWhenForgotten' => ['type' => 'bool'],
				'notifiedWhenTaken' => ['type' => 'bool'],
				'notifiedWhenOffline' => ['type' => 'bool'],
				'notificationDelay' => ['type' => 'interval'],
			],
			'relationships' => [
				'caregiverUser' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'caringFor',
					'owner' => true,
				],

				'caredForUser' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'caregivers',
					'owner' => true,
				]
			]
		],

		'Prescription' => [
			'storage' => defined('QS1') ? [
				'primary' => 'medications'
			] : null,

			'attributes' => [
				'packaging' => ['type' => 'string', 'values' => ['In A Packet', 'Separate Bottle']],
				'frequency' => ['type' => 'string'],
				'autoRefill' => ['type' => 'bool'],
				'days' => ['type' => 'string'],
				'quantity' => ['type' => 'string'],
				'startedAt' => ['type' => 'date'],
				'monographUrl' => ['type' => 'string'],

				'type' => ['type' => 'string'],

				'image' => ['type' => 'string'],

				'directions' => ['type' => 'string'],
				'name' => ['type' => 'string'],
				'prescriber' => ['type' => 'string'],
				'rxNumber' => ['type' => 'string'],
				'endDate' => ['type' => 'date'],
			],
			'relationships' => [
				'user' => [
					'type' => 'One',
					'model' => 'User',
					'inverseRelationship' => 'prescriptions',
					'owner' => true,
				],
			],
		],

		'MedicineLogEntry' => [
			'attributes' => [
				'timestamp' => ['type' => 'datetime'],
				'time' => ['type' => 'string'],
				'event' => ['type' => 'string'],
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'medicineLogEntries',
					'owner' => true,
				],
				'doses' => [
					'model' => 'MedicineLogEntryDose',
					'type' => 'Many',
					'inverseRelationship' => 'medicineLogEntry'
				]
			]
		],

		'MedicineLogEntryDose' => [
			'attributes' => [
				'quantity' => ['type' => 'string'],
			],
			'relationships' => [
				'prescription' => [
					'model' => 'Prescription',
					'type' => 'One'
				],
				'medicineLogEntry' => [
					'model' => 'MedicineLogEntry',
					'type' => 'One',
					'inverseRelationship' => 'doses',
					'owner' => true
				]
			]
		],

		'Address' => [
			'storage' => defined('QS1') ? [
				'primary' => 'addresses'
			] : null,
			'attributes' => [
				'street1' => ['type' => 'string'],
				'street2' => ['type' => 'string'],
				'name' => ['type' => 'string'],
				'city' => ['type' => 'string'],
				'state' => ['type' => 'string'],
				'zip' => ['type' => 'string'],
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'addresses',
					'owner' => true
				]
			]
		],

		'Allergy' => [
			'attributes' => [
				'name' => ['type' => 'string'],
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'allergies',
					'owner' => true,
				]
			]
		],
		'Condition' => [
			'attributes' => [
				'name' => ['type' => 'string'],
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'conditions',
					'owner' => true,
				]
			]
		],

		'PaymentMethod' => [
			'storage' => [
				'primary' => 'paymentMethods',
			],
			'attributes' => [
				'firstName' => ['type' => 'string'],
				'lastName' => ['type' => 'string'],
				'number' => ['type' => 'string'],
				'type' => ['type' => 'string'],
				'cvc' => ['type' => 'string'],
				'expMonth' => ['type' => 'string'],
				'expYear' => ['type' => 'string'],

				'street1' => ['type' => 'string'],
				'street2' => ['type' => 'string'],
				'zip' => ['type' => 'string'],
				'city' => ['type' => 'string'],
				'state' => ['type' => 'string'],
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'paymentMethods',
					'owner' => true,
				]
			]
		],

		'Insurance' => [
			'storage' => defined('QS1') ? [
				'primary' => 'insurance'
			] : null,

			'attributes' => [
				'idNumber' => ['type' => 'string'],
				'groupNumber' => ['type' => 'string'],
				'rxBin' => ['type' => 'string']
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'insurance',
					'owner' => true,
				]
			]
		],

		'Payment' => [
			'attributes' => [
				'timestamp' => ['type' => 'datetime'],
				'amount' => ['type' => 'float'],
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'payments',
					'owner' => true,
				],
				'paymentMethod' => [
					'model' => 'PaymentMethod',
					'type' => 'One'
				]
			]
		],

		'Bill' => [
			'attributes' => [
				'timestamp' => ['type' => 'datetime'],
				'amount' => ['type' => 'float'],
			],
			'relationships' => [
				'user' => [
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'bills',
					'owner' => true,
				]
			]
		],
	],
	'resources' => [
		'user' => [
			'root' => 'User',

			'models' => [
				'MedicineLogEntryDose' => [
					'references' => ['prescription'],
				],
				'Payment' => [
					'references' => ['paymentMethod'],
				],
				'User' => [
					'references' => ['currentAddress', 'currentPaymentMethod', 'currentInsurance'],
					'edges' => ['caregivers', 'caringFor'],
				]
			],

			'nodes' => [
				'caregivers.caregiverUser' => [
					'edges' => false,

						'references' => ['currentAddress', 'currentPaymentMethod', 'currentInsurance'],

//					'edges' => array('caregivers', 'caringFor'),

				],
				'caringFor.caredForUser' => [
					'edges' => [
						'caregivers' => false,
						'caringFor' => false,

						'references' => ['currentAddress', 'currentPaymentMethod', 'currentInsurance'],

						'edges' => ['caregivers', 'caringFor'],

					]
				],

				// MedicineLogEntryDose
				'medicineLogEntries.doses' => [
					'references' => ['prescription'],
				],
				'caringFor.caredForUser.medicineLogEntries.doses' => [
					'references' => ['prescription'],
				],

				// Payment
				'payments' => [
					'references' => ['paymentMethod'],
				],
				'caringFor.caredForUser.payments' => [
					'references' => ['paymentMethod'],
				],

				// User
				'' => [
					'references' => ['currentAddress', 'currentPaymentMethod', 'currentInsurance'],
					'edges' => ['caregivers', 'caringFor'],
				],
			]
		]
	],
	'routes' => [
		'/' => [
			'type' => 'db'
		],

		'/u' => [
			[
				'type' => 'model',
				'params' => function($client) {
					return [
						'model' => 'User',
						'id' => $client['userId']
					];
				}
			],
		],

		'/user' => [
			'type' => 'resource',
			'resource' => 'user',
			'id' => function($client) {
				return $client['userId'];
			}
		],

		'/:model/:id' => [
			'type' => 'model'
		],
	]
];






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
