<?php

return array(
	'databases' => array(
		'default' => 'mongodb',
		// 'mysql' => array(
		// 	'type' => 'mysql',
		// 	'db' => 'journal',
		// 	'server' => '127.0.0.1',
		// 	'user' => 'root',
		// 	'password' => '9wo7bCrA'
		// ),
		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'life',
		),
	),

	// 'queryProfiles' => array(
	// 	'default' => array(
	// 		'Model' => array(
	// 			'attributes' => array(
	// 				'hash' => array(
	// 					'retrieval' => 'lazy'
	// 				)
	// 			),
	// 			'relationships' => array(
	// 				'children' => array(
	// 					'paged' => 10
	// 				)
	// 			)
	// 		)
	// 	)
	// ),

	'models' => array(
		'Activity' => array(
			'attributes' => array(
				'name' => array(
					'type' => 'string',
				),
			),
			'relationships' => array(
				'instances' => array(
					'type' => 'Many',
					'model' => 'Instance',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'activity',
					'storage' => array(
						'foreignKey' => 'activity_id',
					)
				)
			)
		),
		'Instance' => array(
			'attributes' => array(
				'begin' => array(
					'type' => 'datetime',
				),
				'end' => array(
					'type' => 'datetime',
				),
			),
			'relationships' => array(
				'activity' => array(
					'type' => 'One',
					'model' => 'Activity',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'instances',
					'storage' => array(
						'key' => 'activity_id',
					)
				)
			)
		),
		'Goal' => array(
			'attributes' => array(
				'name' => array(
					'type' => 'string'
				)
			)
		),
		'Day' => array(
			'attributes' => array(
				'begin' => array(
					'type' => 'datetime',
				),
				'end' => array(
					'type' => 'datetime',
				),
				'intendedEnd' => array(
					'type' => 'datetime',
				)
			)
		),
		'Thought' => array(
			'attributes' => array(
				'content' => array(
					'type' => 'string',
				),
				'timestamp' => array(
					'type' => 'datetime',
				),
			)
		),

		// '@Money' => array(
			'RecurringBill' => array(
				'attributes' => array(
					'date' => array('type' => 'string'),
					'label' => array('type' => 'string'),
					'amount' => array('type' => 'float')
				),
				'relationships' => array(
					'payments' => array(
						'type' => 'Many',
						'model' => 'MoneyEvent',
						'inverseRelationship' => 'recurringBill'
					)
				)
			),

			'Income' => array(
				'attributes' => array(
					'amount' => array('type' => 'float'),
					'label' => array('type' => 'string')
				)
			),

			'Budget' => array(
				'attributes' => array(
					'amount' => array('type' => 'float'),
					'label' => array('type' => 'string')
				)
			),

			'MoneyStore' => array(
				'attributes' => array(
					'name' => array('type' => 'string'),
					'currency' => array('type' => 'string'),
					'type' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'events' => array(
						'type' => 'Many',
						'model' => 'MoneyEvent',
						'inverseRelationship' => 'store'
					)
				)
			),

			'MoneyEvent' => array(
				'attributes' => array(
					'timestamp' => array('type' => 'datetime'),
					'type' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
				),
				'relationships' => array(
					'store' => array(
						'type' => 'One',
						'model' => 'MoneyStore',
						'inverseRelationship' => 'events',
					),
					'recurringBill' => array(
						'type' => 'One',
						'model' => 'RecurringBill',
						'inverseRelationship' => 'payments'
					)
				)
			),

			'DuePayment' => array(
				'attributes' => array(
					'label' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
					'timestamp' => array('type' => 'datetime'),
					'due' => array('type' => 'datetime')
				)
			),

			'Loan' => array(
				'attributes' => array(
					'label' => array('type' => 'string'),
					'amount' => array('type' => 'float'),
					'timestamp' => array('type' => 'datetime'),
				)
			),
		// ),

		// 'SleepBe'
	),

	'routes' => array(
		'/' => array(
			'type' => 'db'
		),

		'/:model/:id' => array(
			'type' => 'model'
		),
		// '/Model' => array('model' => 'Model'),

		// '/settings/:id' => array(
		// 	'type' => 'hash',
		// 	'storage' => array(
		// 		'db' => 'mongodb',
		// 		'collection' => 'settings',
		// 	)
		// )
	)
);