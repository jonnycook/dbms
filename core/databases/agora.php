<?php

function referenceModel($parent) {
	return [
			'relationships' => [
				'item' => [
					'type' => 'One',
					'model' => ['Product', 'Bundle'],
				],
				'parent' => [
					'model' => $parent,
					'type' => 'One',
					'inverseRelationship' => 'itemReferences'
				]
			]
		];
}

return [
	'databases' => [
		'default' => 'mongodb',
		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'agora',
		]
	],

	'models' => [
		'Product' => [
			'attributes' => [
				'title' => ['type' => 'string'],
				'image' => ['type' => 'string'],
				'price' => ['type' => 'string'],
				'siteName' => ['type' => 'string'],
				'productSid' => ['type' => 'string'],
				'status' => ['type' => 'string'],
			]
		],

		'Bundle' => [
			'relationships' => [
				'itemReferences' => [
					'model' => 'BundleItemReference',
					'type' => 'Many',
					'inverseRelationship' => 'parent'
				]
			]
		],
		'BundleItemReference' => referenceModel('Bundle'),

		'Belt' => [
			'relationships' => [
				'itemReferences' => [
					'model' => 'BeltItemReference',
					'type' => 'Many',
					'inverseRelationship' => 'parent'
				]
			],
		],

		'BeltItemReference' => referenceModel('Belt'),
	],

	'routes' => [
		'/' => [
			'type' => 'db'
		],

		'/:model/:id' => [
			'type' => 'model'
		],
	]
];