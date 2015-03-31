<?php

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
			'attributes' => array(
				'fullName' => array('type' => 'string'),
				'timezone' => array('type' => 'int'),
				'sex' => array('type' => 'string'),
				'email' => array('type' => 'string'),
				'dateOfBirth' => array('type' => 'string'),
				'phoneNumber' => array('type' => 'string'),
			),
			'relationships' => array(
				'caregivers' => array(
					'model' => 'Caregiver',
					'type' => 'Many',
					'inverseRelationship' => 'caregivingUser'
				),
				'prescriptions' => array(
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
					'model' => 'Address',
					'type' => 'Many',
					'inverseRelationship' => 'user'
				)
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
			'attributes' => array(
				'packaging' => array('type' => 'string', 'values' => array('In A Packet', 'Separate Bottle')),
				'frequency' => array('type' => 'string'),
				'autoRefill' => array('type' => 'bool'),
				'days' => array('type' => 'string'),
				'quantity' => array('type' => 'string'),
				'startedAt' => array('type' => 'datetime'),
			),
			'relationships' => array(
				'user' => array(
					'type' => 'One',
					'model' => 'User',
					'inverseRelationship' => 'prescriptions'
				),
				'supplementStrength' => array(
					'type' => 'One',
					'model' => 'SupplementStrength',
				),
				'doses' => array(
					'type' => 'Many',
					'model' => 'PrescriptionDose',
					'inverseRelationship' => 'prescription'
				)
			),
		),

		'PrescriptionDose' => array(
			'attributes' => array(
				'time' => array('type' => 'string'),
				'quantity' => array('type' => 'string')
			),
			'relationships' => array(
				'prescription' => array(
					'type' => 'One',
					'model' => 'Prescription',
					'inverseRelationship' => 'doses'
				)
			)
		),

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

				'deviceToken' => array('type' => 'string'),

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
			'attributes' => array(
				'street1' => array('type' => 'string'),
				'street2' => array('type' => 'string'),
				'name' => array('type' => 'string'),
				'city' => array('type' => 'string'),
				'state' => array('type' => 'string'),
				'zipCode' => array('type' => 'string'),
			),
			'relationships' => array(
				'user' => array(
					'model' => 'User',
					'type' => 'One',
					'inverseRelationship' => 'addresses'
				)
			)
		)
	),

	'routes' => array(
		'/' => array(
			'type' => 'db'
		),

		'/:model/:id' => array(
			'type' => 'model'
		),
	)
);