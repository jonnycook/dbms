<?php

return [
	'databases' => [
		'default' => 'mongodb',
		'mongodb' => [
			'type' => 'mongodb',
			'db' => 'imageorganizer',
		],
	],

	'models' => [
		'Image' => [
			'attributes' => [
				'hash' => ['type' => 'string'],
				'addedAt' => ['type' => 'datetime'],
			],
			'relationships' => [
				'points' => [
					'model' => 'Point',
					'type' => 'Many',
					'inverseRelationship' => 'image'
				],
				'nullValues' => [
					'model' => 'DimensionValue',
					'type' => 'Many',
				]
			]
		],

		'Point' => [
			'attributes' => [
				'region' => ['type' => 'string'],
			],
			'relationships' => [
				'image' => [
					'model' => 'Image',
					'type' => 'One',
					'inverseRelationship' => 'points'
				],
				'dimensionValues' => [
					'model' => 'DimensionValue',
					'type' => 'Many',
					'inverseRelationship' => 'points'
				],
				'parent' => [
					'model' => 'Point',
					'type' => 'One',
					'inverseRelationship' => 'points',
				],
				'points' => [
					'model' => 'Point',
					'type' => 'Many',
					'inverseRelationship' => 'parent'
				]
			]
		],

		'DimensionValue' => [
			'attributes' => [
				'label' => ['type' => 'string'],
			],
			'relationships' => [
				'dimension' => [
					'model' => 'Dimension',
					'type' => 'One',
					'inverseRelationship' => 'values'
				],
				'parentValue' => [
					'model' => 'DimensionValue',
					'type' => 'One',
				],
				'points' => [
					'model' => 'Point',
					'type' => 'Many',
					'inverseRelationship' => 'dimensionValues',
				]
			]
		],

		'Dimension' => [
			'attributes' => [
				'name' => ['type' => 'string'],
			],
			'relationships' => [
				'values' => [
					'model' => 'DimensionValue',
					'type' => 'Many',
					'inverseRelationship' => 'dimension'
				],
				'nullValue' => [
					'model' => 'DimensionValue',
					'type' => 'One',
				]
			]
		],

		'DimensionValueDistance' => [
			'attributes' => [
				'amount' => ['type' => 'float']
			],

			'relationships' => [
				'valueA' => [
					'model' => 'DimensionValue',
					'type' => 'One',
				],
				'valueB' => [
					'model' => 'DimensionValue',
					'type' => 'One',
				],
			]
		],

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