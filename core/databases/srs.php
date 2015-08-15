<?php

return [
	'databases' => [
		'default' => 'mysql',
		'mysql' => [
			'type' => 'mysql',
			'db' => 'srs',
			'server' => '127.0.0.1',
			'user' => 'root',
			'password' => ''
		],

		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'srs',
		],
	],

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

	'models' => [
		'Entry' => [
			'storage' => [
				'config' => [
					'mysql' => [
						'table' => 'srs_entries'
					]
				]
			],
			'attributes' => [
				'factor' => [
					'type' => 'int',
				],
				'ivl' => [
					'type' => 'int',
				],
				'stage' => [
					'type' => 'int',
				],
				'learning_interval' => [
					'type' => 'int',
				],
				'learning_remembered' => [
					'type' => 'int',
				],
				'remembering_ease' => [
					'type' => 'int',
				],
				'last_reviewed_at' => [
					'type' => 'datetime',
				],
				'disabled' => [
					'type' => 'bool',
				],
				'sides' => [
					'type' => 'string',
					'storage' => [
						'dependencies' => ['mysql.record'],
						'compute' => function($entry) {
							switch ($entry['item_type']) {
								case '1': $table = 'terms'; break;
							}
							$item = mysql_fetch_assoc(mysql_query("SELECT * FROM $table WHERE id = $entry[item_id]"));

							switch ($entry['item_type']) {
								case '1':
									$front = utf8_encode("<span class='namespace'>($item[namespace])</span> $item[term]");
									$back = utf8_encode($item['definition']);
									break;
							}

							return ['front' => $front, 'back' => $back];
						}
					]
				]
			],
			'relationships' => [
				'log' => [
					'type' => 'Many',
					'model' => 'Log',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'entry',
					'storage' => [
						'foreignKey' => 'entry_id',
					]
				]
			]
		],
		'Log' => [
			'storage' => [
				'config' => [
					'mysql' => [
						'table' => 'srs_log'
					]
				]
			],
			'attributes' => [
				'stage' => [
					'type' => 'int'
				],
				'answer' => [
					'type' => 'string'
				],
				'timestamp' => [
					'type' => 'datetime'
				]
			],
			'relationships' => [
				'entry' => [
					'type' => 'One',
					'model' => 'Entry',
					'inverseRelationship' => 'log',
					'storage' => [
						'key' => 'entry_id'
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