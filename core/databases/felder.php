<?php

return [
	'databases' => [
		'default' => 'mongodb',
		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'felder',
		],
	],
	'models' => [
		'images' => [
			'storage' => [
				'config' => [
					'mongodb' => ['id' => ['auto' => false]]
				]
			],
			'attributes' => [
				'width' => ['type' => 'int'],
				'height' => ['type' => 'int'],
				'createdAt' => ['type' => 'datetime'],
			]
		],
	],
	// 'resources' => [
	// 	'images' => [

	// 	],
	// ],

	'routes' => [
		'/' => [
			'type' => 'db',
		]
	]
];