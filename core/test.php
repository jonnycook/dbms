<?php

require_once('main.php');

$databaseName = 'test';
$databaseSchema = require("databases/$databaseName.php");

$data = array(
	'Model' => array(
		'$1' => array(
			'prop' => '1',
			'parent' => '$2',
			'hash' => array(
				'yes' => true
			)
		),
		'$2' => array(
			'prop' => '2',
		),
		'$3' => array(
			'prop' => '3',
			'parent' => '$2',
		)
	)
);

$mapping = array();
foreach ($data as $model => $modelChanges) {
	foreach ($modelChanges as $id => $changes) {
		if (!isTemporaryId($id) || !$mapping[$id]) {
			updateObject($databaseSchema, $model, $id, $changes, $mapping, $data);
		}
	}
}
var_dump($mapping);

$results = array();
getObject($databaseSchema, 'Model', $mapping['$1'], $results);
var_dump($results);

// updateObject($databaseSchema, 'Model', $mapping['$1'], array(
// 	'prop' => '3',
// 	'hash' => array(
// 		'yes' => false,
// 		'array' => array(1, 2)
// 	)
// ), $mapping);

// echo "2\n";

// var_dump(getObject($databaseSchema, 'Model', $mapping['$1']));


// updateObject($databaseSchema, 'Model', $mapping['$1'], array(
// 	'prop' => '3',
// 	'hash.key' => 'value',
// 	// 'hash.array[1]' => 2,
// 	// 'hash.array[2]' => 3,
// 	// 'array[]' => 10,
// 	// 'array[^]' => 1,
// 	// 'array()' => 'push(1)',
// 	// 'array()' => 'unshift(1)',
// 	// 'array()' => 'shift',
// 	// 'array()' => 'pop',
// 	// 'array()' => array('pop'),
// 	// 'array()' => array('push', 1),
// 	// 'array[2].array[]' => 1,
// 	// 'array.2' => 1,
// 	// 'hash.array()' => array('unshift', 1)
// ), $mapping);
// echo "3\n";


// var_dump(getObject($databaseSchema, 'Model', $mapping['$1']));

// // updateObject($databaseSchema, 'Model', $mapping['$1'], 'delete');

// // get('/Model/1')