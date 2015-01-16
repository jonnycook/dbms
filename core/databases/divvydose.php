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

		'ClientSchedule' => array(
			'attributes' => array(
				'clientId' => array('type' => 'string'),

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