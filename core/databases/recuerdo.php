<?php

return array(
	'databases' => array(
		'default' => 'mysql',
		'mysql' => array(
			'type' => 'mysql',
			'db' => 'knowledge',
			'server' => '127.0.0.1',
			'user' => 'root',
			'password' => '9wo7bCrA'
		),

		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'recuerdo',
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
		'Entry' => array(
			'storage' => array(
				'config' => array(
					'mysql' => array(
						'table' => 'srs_entries'
					)
				)
			),
			'attributes' => array(
				'factor' => array(
					'type' => 'int',
				),
				'ivl' => array(
					'type' => 'int',
				),
				'stage' => array(
					'type' => 'int',
				),
				'learning_interval' => array(
					'type' => 'int',
				),
				'learning_remembered' => array(
					'type' => 'int',
				),
				'remembering_ease' => array(
					'type' => 'int',
				),
				'last_reviewed_at' => array(
					'type' => 'datetime',
				),
				'disabled' => array(
					'type' => 'bool',
				),
				'front' => array(
					'type' => 'string',
					'storage' => array(
						'dependencies' => array('mysql', 'mysql.record'),
						'compute' => function($mysql, $entry) {
							switch ($entry['item_type']) {
								case 1: $table = 'terms'; break;
							}
							$item = mysql_fetch_assoc(mysql_query("SELECT * FROM $table WHERE id = $entry[item_id]"));

							switch ($entry['item_type']) {
								case 1:
									$front = "<span class='namespace'>($item[namespace])</span> $item[term]";
									$back = $item['definition'];
									break;
							}

							return $front;
						}
					)
				)
			),
			'relationships' => array(
				'log' => array(
					'type' => 'Many',
					'model' => 'Log',
					'structure' => 'OrderedSet', // Set, OrderedSet, ArrangedSet
					'inverseRelationship' => 'entry',
					'storage' => array(
						'foreignKey' => 'entry_id',
					)
				)
			)
		),
		'Log' => array(
			'storage' => array(
				'config' => array(
					'mysql' => array(
						'table' => 'srs_log'
					)
				)
			),
			'attributes' => array(
				'stage' => array(
					'type' => 'int'
				),
				'answer' => array(
					'type' => 'string'
				),
				'timestamp' => array(
					'type' => 'datetime'
				)
			),
			'relationships' => array(
				'entry' => array(
					'type' => 'One',
					'model' => 'Entry',
					'inverseRelationship' => 'log',
					'storage' => array(
						'key' => 'entry_id'
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