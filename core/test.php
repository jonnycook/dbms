<?php

// require_once('main.php');

$databaseName = 'divvydose';
$databaseSchema = require("databases/$databaseName.php");




	function mapObject($rules, $object, $state, &$output) {
		if (is_array($rules)) {
			foreach ($rules as $ruleKey => $ruleValue) {
				if ($ruleValue === null) {
					$output[] = $state;
					return null;
				}
				else if ($ruleKey[0] == '@') {
					$objectProp = substr($ruleKey, 1);
					if ($objectProp == '*') {
						$result = [];
						foreach ($object as $key => $value) {
							$result[] = mapObject($ruleValue, $value, $state, $output);
						}
						return $result;
					}
					else {
						return mapObject($ruleValue, $object[$objectProp], $state, $output);
					}
				}
				else if ($ruleKey == '*') {
					$result = mapObject($ruleValue, $object, $state, $output);
					foreach ($result as $key => $value) {
						if (!isset($state[$key])) {
							$state[$key] = $value;
						}
					}
				}
				else {
					$state[$ruleKey] = mapObject($ruleValue, $object, $state, $output);
				}
			}
		}
		else {
			$prop = substr($rules, 1);
			if ($prop == '*') return $object;
			else {
				return $object[$prop];
			}
		}
	}

$supplements = json_decode(file_get_contents('supplements.json'), true);

mapObject($databaseSchema['models']['SupplementStrength']['storage']['config']['supplements'], $supplements, [], $output);
header('Content-Type: text/plain');
var_dump($output);

// $data = array(
// 	'Model' => array(
// 		'$1' => array(
// 			'prop' => '1',
// 			'parent' => '$2',
// 			'hash' => array(
// 				'yes' => true
// 			)
// 		),
// 		'$2' => array(
// 			'prop' => '2',
// 		),
// 		'$3' => array(
// 			'prop' => '3',
// 			'parent' => '$2',
// 		)
// 	)
// );

// $mapping = array();
// foreach ($data as $model => $modelChanges) {
// 	foreach ($modelChanges as $id => $changes) {
// 		if (!isTemporaryId($id) || !$mapping[$id]) {
// 			updateObject($databaseSchema, $model, $id, $changes, $mapping, $data);
// 		}
// 	}
// }
// var_dump($mapping);

// $results = array();
// getObject($databaseSchema, 'Model', $mapping['$1'], $results);
// var_dump($results);

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