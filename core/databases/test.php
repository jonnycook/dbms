<?php

return array(
	'databases' => array(
		'default' => 'mysql',
		'mysql' => array(
			'type' => 'mysql',
			'db' => 'dbms',
			'server' => '127.0.0.1',
			'user' => 'root',
			'password' => '9wo7bCrA'
		),
		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'dbms',
		),
	),

	'queryProfiles' => array(
		'default' => array(
			'Model' => array(
				'attributes' => array(
					'hash' => array(
						'retrieval' => 'lazy'
					)
				),
				'relationships' => array(
					'children' => array(
						'paged' => 10
					)
				)
			)
		)
	),

	'models' => array(
		'Model' => array(
			'storage' => array(
				'primary' => 'mysql',
				'config' => array(
					'mysql' => array(
						'table' => 'objects',
					),
					'mongodb' => array(
						'collection' => 'objects'
					)
				)
			),
			'structure' => 'Set',
			'id' => array(
				'type' => 'int'
			),
			'attributes' => array(
				'prop' => array(
					'type' => 'string',
				),
				'hash' => array(
					'type' => 'hash',
					'storage' => array(
						'db' => 'mongodb'
					)
				)
			),
			'relationships' => array(
				'children' => array(
					'type' => 'Many',
					'model' => 'Model',
					'structure' => 'Set', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'parent',
					'storage' => array(
						'foreignKey' => 'parent_id',
					)
				),
				'parent' => array(
					'type' => 'One',
					'model' => 'Model',
					'inverseRelationship' => 'children',
					'storage' => array(
						'key' => 'parent_id'
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