<?php

return array(
	'databases' => array(
		'default' => 'mongodb',
		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'agora',
		)
	),

	'models' => array(
		'Product' => array(
			'attributes' => array(
				'title' => array('type' => 'string')
			)
		),
		// 'Belt' => array(
		// 	''
		// )
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