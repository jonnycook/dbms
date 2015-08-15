<?php

return [
	'databases' => [
		'default' => 'mongodb',
		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'knowledge',
		],
	],
	'models' => [
		'Event' => [
			'attributes' => [
				'action' => ['type' => 'string'],
				'time' => ['type' => 'string'],
			],
			'relationships' => [
				'agents' => [
					'type' => 'Many',
					'model' => 'Entity',
				],
				'objects' => [
					'type' => 'Many',
					'model' => 'Entity'
				]
			]
		],

		'Name' => [
			'relationships' => [
				'primaryForm' => [
					'type' => 'One',
					'model' => 'NameForm',
				],
				'forms' => [
					'type' => 'Many',
					'model' => 'NameForm',
					'inverseRelationship' => 'name'
				]
			]
		],
		'NameForm' => [
			'attributes' => [
				'value' => ['type' => 'string']
			],
			'relationships' => [
				'name' => [
					'type' => 'One',
					'model' => 'Name',
					'inverseRelationship' => 'forms'
				]
			]
		],

		'Entity' => [
			'relationships' => [
				'primaryName' => [
					'type' => 'One',
					'model' => 'Name',
				],
				'names' => [
					'type' => 'Many',
					'model' => 'Name',
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