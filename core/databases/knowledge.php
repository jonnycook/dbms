<?php

return array(
	'databases' => array(
		'default' => 'mongodb',
		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'knowledge',
		),
	),
	'models' => array(
		'Event' => array(
			'attributes' => array(
				'action' => array('type' => 'string'),
				'time' => array('type' => 'string'),
			),
			'relationships' => array(
				'agents' => array(
					'type' => 'Many',
					'model' => 'Entity',
				),
				'objects' => array(
					'type' => 'Many',
					'model' => 'Entity'
				)
			)
		),

		'Name' => array(
			'relationships' => array(
				'primaryForm' => array(
					'type' => 'One',
					'model' => 'NameForm',
				),
				'forms' => array(
					'type' => 'Many',
					'model' => 'NameForm',
					'inverseRelationship' => 'name'
				)
			)
		),
		'NameForm' => array(
			'attributes' => array(
				'value' => array('type' => 'string')
			),
			'relationships' => array(
				'name' => array(
					'type' => 'One',
					'model' => 'Name',
					'inverseRelationship' => 'forms'
				)
			)
		),

		'Entity' => array(
			'relationships' => array(
				'primaryName' => array(
					'type' => 'One',
					'model' => 'Name',
				),
				'names' => array(
					'type' => 'Many',
					'model' => 'Name',
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