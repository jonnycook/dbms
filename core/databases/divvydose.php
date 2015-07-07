<?php

define('QS1_SERVER', '52.27.135.117');
define('QS1_PHARMACY', 'divvyDOSE');
// define('QS1_SERVER', 'sandbox.qs1api.com');
// define('QS1_PHARMACY', 'VendorTest');

return array(
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
		)
	),

	'models' => array(
		'Supplement' => array(
			'storage' => array(
				'primary' => 'supplements',
				'config' => array(
					'supplements' => array(
						'@*' => array(
							'strengths' => array(
								'@strengths' => array(
									'@*' => '@id'
								)
							),
							'*' => '@*',
							null
						)
					)
				)
			),

			'attributes' => array(
				'name' => array('type' => 'string'),
				'primaryName' => array('type' => 'string'),
				'image' => array('type' => 'string'),
			),

			'relationships' => array(
				'strengths' => array(
					'type' => 'Many',
					'model' => 'SupplementStrength',
					'inverseRelationship' => 'supplement',
				)
			)
		),

		'SupplementStrength' => array(
			'storage' => array(
				'primary' => 'supplements',
				'config' => array(
					'supplements' => array(
						'@*' => array(
							'supplement' => '@id',
							'@strengths' => array(
								'@*' => array(
									'*' => '@*',
									null
								)
							)
						)
					)
				)
			),
			'attributes' => array(
				'name' => array('type' => 'string'),
				'retailItemCost' => array('type' => 'float'),
				'doseType' => array('type' => 'string'),
				'containerType' => array('type' => 'string'),
				'unitSize' => array('type' => 'string'),
				'canBeSubdivided' => array('type' => 'bool'),
				'inPackets' => array('type' => 'bool')
			),
			'relationships' => array(
				'supplement' => array(
					'type' => 'One',
					'model' => 'Supplement',
					'inverseRelationship' => 'strengths'
				)
			)
		),

		'User' => array(
			'storage' => array(
				'filter' => function(&$user) {
					if ($user['divvyPacks']) {
						foreach ($user['divvyPacks'] as $beginDate => $divvyPack) {
							$rxs = array();
							foreach ($divvyPack as $dose) {
								if (!$rxs[$rxNumber = $dose['rxNumber']]) {
									$rxs[$rxNumber] = array(
										'prescriber' => 'Prescriber',
										'name' => 'Fiddle Sticks ' . $rxNumber,
										'sig' => 'Just do it. &tm'
									);
								}
							}
							$user['divvyPacks'][$beginDate] = array(
								'prescriptions' => $rxs,
								'doses' => $divvyPack,
							);
						}
					}
					if ($user['picture']) {
						$user['picture'] = substr($user['picture'], 5);
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
					'storage' => array(
						'db' => 'patients',
					),
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

				// 'passwordHash' => array('type' => 'string'),
				// 'passwordSalt' => array('type' => 'string'),
			),
			'relationships' => array(
				'caregivers' => array(
					'model' => 'Caregiver',
					'type' => 'Many',
					'inverseRelationship' => 'caregivingUser'
				),
				'caringFor' => array(
					'model' => 'Caregiver',
					'type' => 'Many',
					'inverseRelationship' => 'caregiverUser',
				),
				'prescriptions' => array(
					'storage' => array(
						'db' => 'medications'
					),
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
					'storage' => array(
						'db' => 'addresses'
					),
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
					'type' => 'One'
				),
				'caregivingUser' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'caregivers'
				)
			)
		),

		'Prescription' => array(
			'storage' => array(
				'primary' => 'medications'
			),

			'attributes' => array(
				'packaging' => array('type' => 'string', 'values' => array('In A Packet', 'Separate Bottle')),
				'frequency' => array('type' => 'string'),
				'autoRefill' => array('type' => 'bool'),
				'days' => array('type' => 'string'),
				'quantity' => array('type' => 'string'),
				'startedAt' => array('type' => 'date'),

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
					'inverseRelationship' => 'prescriptions'
				),
				'supplementStrength' => array(
					'storage' => array('ignore' => true),
					'type' => 'One',
					'model' => 'SupplementStrength',
				),
				'doses' => array(
					'storage' => array('ignore' => true),
					'type' => 'Many',
					'model' => 'PrescriptionDose',
					'inverseRelationship' => 'prescription'
				)
			),
		),

		// 'PrescriptionDose' => array(
		// 	'attributes' => array(
		// 		'time' => array('type' => 'string'),
		// 		'quantity' => array('type' => 'string')
		// 	),
		// 	'relationships' => array(
		// 		'prescription' => array(
		// 			'type' => 'One',
		// 			'model' => 'Prescription',
		// 			'inverseRelationship' => 'doses'
		// 		)
		// 	)
		// ),

		'MedicineLogEntry' => array(
			'attributes' => array(
				'timestamp' => array('type' => 'datetime'),
				'time' => array('type' => 'string'),
				'date' => array('type' => 'string'),
				'event' => array('type' => 'string'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'medicineLogEntries',
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
				'supplementStrength' => array(
					'model' => 'SupplementStrength',
					'type' => 'One'
				),
				'medicineLogEntry' => array(
					'model' => 'MedicineLogEntry',
					'type' => 'One',
					'inverseRelationship' => 'doses'
				)
			)
		),

		'Client' => array(
			'attributes' => array(
				'clientId' => array('type' => 'string'),

				'devicePlatform' => array('type' => 'string'),
				'deviceToken' => array('type' => 'string'),
				'deviceId' => array('type' => 'string'),

				'schedule' => array('type' => 'string'),

				'everyDay' => array('type' => 'string'),
				'everyOddDay' => array('type' => 'string'),
				'everyEvenDay' => array('type' => 'string'),

				'monday' => array('type' => 'string'),
				'tuesday' => array('type' => 'string'),
				'wednesday' => array('type' => 'string'),
				'thursday' => array('type' => 'string'),
				'friday' => array('type' => 'string'),
				'saturday' => array('type' => 'string'),
				'sunday' => array('type' => 'string'),
			)
		),

		'Address' => array(
			'storage' => array(
				'primary' => 'addresses'
			),
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
					'inverseRelationship' => 'addresses'
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
					'inverseRelationship' => 'allergies'
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
					'inverseRelationship' => 'conditions'
				)
			)
		),

		'PaymentMethod' => array(
			'attributes' => array(
				'nameOnCard' => array('type' => 'string'),
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
					'inverseRelationship' => 'paymentMethods'
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
					'inverseRelationship' => 'insurance'
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
					'inverseRelationship' => 'payments'
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
				)
			)
		),
	),

	'routes' => array(
		'/' => array(
			'type' => 'db'
		),

		'/u' => array(
			array(
				'type' => 'model',
				'params' => array('model' => 'Supplement'),
			),
			array(
				'type' => 'model',
				'params' => array('model' => 'SupplementStrength'),
			),
			array(
				'type' => 'model',
				'params' => function($client) {
					$session = _mongoClient()->divvydose->sessions->findOne(array('_id' => new MongoId($client['token'])));

					return array(
						'model' => 'User',
						'id' => $session['userId']
					);
				}
			),
		),

		'/:model/:id' => array(
			'type' => 'model'
		),
	)
);