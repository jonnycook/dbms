<?php

return [
	'databases' => [
		'default' => 'mysql',
		'mysql' => [
			'type' => 'mysql',
			'db' => 'dbms',
			'server' => '127.0.0.1',
			'user' => 'root',
			'password' => '9wo7bCrA'
		],
		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'dbms',
		],
	],

	'queryProfiles' => [
		'default' => [
			'Model' => [
				'attributes' => [
					'hash' => [
						'retrieval' => 'lazy'
					]
				],
				'relationships' => [
					'children' => [
						'paged' => 10
					]
				]
			]
		]
	],

	'models' => [
		'Model' => [
			'storage' => [
				'primary' => 'mysql',
				'config' => [
					'mysql' => [
						'table' => 'objects',
					],
					'mongodb' => [
						'collection' => 'objects'
					]
				]
			],
			'structure' => 'Set',
			'id' => [
				'type' => 'int'
			],
			'attributes' => [
				'prop' => [
					'type' => 'string',
				],
				'hash' => [
					'type' => 'hash',
					'storage' => [
						'db' => 'mongodb'
					]
				]
			],
			'relationships' => [
				'children' => [
					'type' => 'Many',
					'model' => 'Model',
					'structure' => 'Set', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'parent',
					'storage' => [
						'foreignKey' => 'parent_id',
					]
				],
				'parent' => [
					'type' => 'One',
					'model' => 'Model',
					'inverseRelationship' => 'children',
					'storage' => [
						'key' => 'parent_id'
					]
				]
			]
		]
	],

	'routes' => [
		'/' => [
			'type' => 'db'
		],

		'/:model/:id' => [
			'type' => 'model'
		],
		// '/Model' => array('model' => 'Model'),

		// '/settings/:id' => array(
		// 	'type' => 'hash',
		// 	'storage' => array(
		// 		'db' => 'mongodb',
		// 		'collection' => 'settings',
		// 	)
		// )
	]
];