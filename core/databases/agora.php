<?php

function referenceModel($parent) {
	return array(
			'relationships' => array(
				'item' => array(
					'type' => 'One',
					'model' => array('Product', 'Bundle'),
				),
				'parent' => array(
					'model' => $parent,
					'type' => 'One',
					'inverseRelationship' => 'itemReferences'
				)
			)
		);
}

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
				'title' => array('type' => 'string'),
				'image' => array('type' => 'string'),
				'price' => array('type' => 'string'),
				'siteName' => array('type' => 'string'),
				'productSid' => array('type' => 'string'),
				'status' => array('type' => 'string'),
			)
		),

		'Bundle' => array(
			'relationships' => array(
				'itemReferences' => array(
					'model' => 'BundleItemReference',
					'type' => 'Many',
					'inverseRelationship' => 'parent'
				)
			)
		),
		'BundleItemReference' => referenceModel('Bundle'),

		'Belt' => array(
			'relationships' => array(
				'itemReferences' => array(
					'model' => 'BeltItemReference',
					'type' => 'Many',
					'inverseRelationship' => 'parent'
				)
			),
		),

		'BeltItemReference' => referenceModel('Belt'),
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