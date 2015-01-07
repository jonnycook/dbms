<?php

return array(
	'databases' => array(
		'default' => 'mongodb',
		'mongodb' => array(
			'type' => 'mongodb',
			'db' => 'imageorganizer',
		),
	),

	'models' => array(
		'Image' => array(
			'attributes' => array(
				'hash' => array('type' => 'string',),
				'addedAt' => array('type' => 'datetime'),
			),
			'relationships' => array(
				'points' => array(
					'model' => 'Point',
					'type' => 'Many',
					'inverseRelationship' => 'image'
				)
			)
		),

		'Point' => array(
			'attributes' => array(
				'region' => array('type' => 'string'),
			),
			'relationships' => array(
				'image' => array(
					'model' => 'Image',
					'type' => 'One',
					'inverseRelationship' => 'points'
				),
				'dimensionValues' => array(
					'model' => 'DimensionValue',
					'type' => 'Many',
					'inverseRelationship' => 'points'
				),
				'parent' => array(
					'model' => 'Point',
					'type' => 'One',
					'inverseRelationship' => 'points',
				),
				'points' => array(
					'model' => 'Point',
					'type' => 'Many',
					'inverseRelationship' => 'parent'
				)
			)
		),

		'DimensionValue' => array(
			'attributes' => array(
				'label' => array('type' => 'string')
			),
			'relationships' => array(
				'dimension' => array(
					'model' => 'Dimension',
					'type' => 'One',
					'inverseRelationship' => 'values'
				),
				'points' => array(
					'model' => 'Point',
					'type' => 'Many',
					'inverseRelationship' => 'dimensionValues',
				)
			)
		),

		'Dimension' => array(
			'attributes' => array(
				'name' => array('type' => 'string')
			),
			'relationships' => array(
				'values' => array(
					'model' => 'DimensionValue',
					'type' => 'Many',
					'inverseRelationship' => 'dimension'
				)
			)
		),

		'DimensionValueDistance' => array(
			'attributes' => array(
				'amount' => array('type' => 'float')
			),

			'relationships' => array(
				'valueA' => array(
					'model' => 'DimensionValue',
					'type' => 'One',
				),
				'valueB' => array(
					'model' => 'DimensionValue',
					'type' => 'One',
				),
			)
		),

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