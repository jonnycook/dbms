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
				'start' => array(
					'type' => 'datetime',
				),
				'end' => array(
					'type' => 'datetime',
				),
				// 'elapsed' => array(
					
				// )
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
		)
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