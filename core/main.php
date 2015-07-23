<?php

header('Content-Type: text/plain');

require_once('includes/header.php');
require_once('includes/schema.php');
require_once('databaseEngines/MongoDbDatabaseEngine.class.php');
require_once('databaseEngines/MysqlDatabaseEngine.class.php');
require_once('databaseEngines/JsonDatabaseEngine.class.php');
require_once('databaseEngines/AddressesDatabaseEngine.class.php');
require_once('databaseEngines/InsuranceDatabaseEngine.class.php');
require_once('databaseEngines/MedicationsDatabaseEngine.class.php');
require_once('databaseEngines/PatientsDatabaseEngine.class.php');

require_once('includes/main.php');

$clientId = $_GET['clientId'];
$databaseName = $_REQUEST['db'];
if ($_REQUEST['schemaVersion']) {
	$schemaVersion = $_REQUEST['schemaVersion'];
}
else {
	$schemaVersion = 1;
}

$databaseSchema = require("databases/$databaseName.php");

if ($clientId) {
	$clientDocument = mongoClient()->clients->findOne(array('_id' => $clientId));
}

if ($databaseSchema['init']) {
	$databaseSchema['init']($clientDocument);
}

if ($resource = $_GET['resource']) {
	$params = array();

	$models = array_keys($databaseSchema['models']);
	foreach ($databaseSchema['routes'] as $route => $routeSchema) {
		preg_match_all('/:([a-z]+)/', $route, $matches);
		$paramNames = $matches[1];

		$pattern = preg_replace_callback('/:([a-z]+)/', function($matches) use ($models) {
			if ($matches[1] == 'model') {
				return '(' . implode('|', $models) . ')';
			}
			else {
				return '(.*?)';
			}
		}, $route);

		if (preg_match("#^$pattern\$#", $resource, $matches)) {
			foreach ($paramNames as $i => $paramName) {
				$params[$paramName] = $matches[$i + 1];
			}
			$matched = true;
			break;
		}
	}

	if ($matched) {
		if (!$routeSchema[0]) {
			$routeSchemas = array($routeSchema);
		}
		else {
			$routeSchemas = $routeSchema;
		}
		$allResults = array();
		foreach ($routeSchemas as $routeSchema) {
			$results = array();
			if (is_callable($routeSchema)) {
				$routeSchema = $routeSchema();
				// $params = array_merge($params, $routeSchema['params']);
			}

			if ($routeSchema['params']) {
				if (is_callable($routeSchema['params'])) {
					$params = array_merge($params, $routeSchema['params']($clientDocument));
				}
				else {
					$params = array_merge($params, $routeSchema['params']);			
				}
			}
			
			unset($resolvedResource);
			if ($routeSchema['type'] == 'db') {
				foreach ($databaseSchema['models'] as $modelName => $modelSchema) {
					$primaryStorageName = schemaModelStorage($databaseSchema, $modelName);
					$storageConfig = schemaModelStorageConfig($databaseSchema, $modelName, $primaryStorageName);
					$storage = storageEngine($databaseSchema, $primaryStorageName);

					$ids = $storage->ids($modelName, $storageConfig);

					if ($ids) {
						foreach ($ids as $id) {
							getObject($databaseSchema, $modelName, $id, $results);
						}
					}
				}

				$resolvedResource = array(
					'type' => 'db',
				);
			}
			else if ($routeSchema['type'] == 'model') {
				if ($params['id']) {
					getObject($databaseSchema, $params['model'], $params['id'], $results);
					$resolvedResource = array(
						'type' => 'model',
						'model' => $params['model'],
						'id' => $params['id'],
					);
				}
				else {
					$primaryStorageName = schemaModelStorage($databaseSchema, $params['model']);
					$storageConfig = schemaModelStorageConfig($databaseSchema, $params['model'], $primaryStorageName);
					$storage = storageEngine($databaseSchema, $primaryStorageName);

					$ids = $storage->ids($params['model'], $storageConfig);
					foreach ($ids as $id) {
						getObject($databaseSchema, $params['model'], $id, $results);
					}

					$resolvedResource = array(
						'type' => 'model',
						'model' => $params['model'],
					);
				}
			}


			if ($clientId && $resolvedResource) addSubscriberToResource($databaseName, $schemaVersion, $resolvedResource, $clientId);

			foreach ($results as $model => $instances) {
				foreach ($instances as $id => $instance) {
					$allResults[$model][$id] = $instance;
				}
				// $allResults[$model] = array_merge((array)$allResults[$model], $instances);
			}
		}

		echo json_encode(array(
			'path' => $resource,
			'data' => $allResults
		) + $resolvedResource);
	}
}
else if ($update = $_POST['update']) {
	$update = json_decode($update, true);

	$updateDoc = mongoClient()->updates->findOne(array('_id' => array('db' => $databaseName, 'id' => $update['id'])));
	if (!$updateDoc) {
		foreach ($update['data'] as $model => $modelChanges) {
			foreach ($modelChanges as $id => $instanceChanges) {
				if ($instanceChanges != 'delete') {
					$withoutDelete[$model][$id] = $instanceChanges;
				}
				else {
					$delete[$model][$id] = $instanceChanges;
				}
			}
		}



		if ($withoutDelete) {
			list($mapping, $resolvedUpdate) = executeUpdate($withoutDelete, $databaseSchema);		
		}

		if ($delete) {
			foreach ($delete as $model => $modelChanges) {
				foreach ($modelChanges as $id => $instanceChanges) {
					$resolvedUpdate[$model][$id] = $instanceChanges;
				}
			}
		}
		// var_dump($resolvedUpdate);

		distributeUpdate($databaseName, $databaseSchema, array('id' => $update['id'], 'data' => $resolvedUpdate), $clientId);

		if ($delete) {
			executeUpdate($delete, $databaseSchema);
		}

		$response = new stdClass();
		if ($mapping) {
			$response->mapping = $mapping;
		}

		mongoClient()->updates->insert(array(
			'_id' => array('db' => $databaseName, 'id' => $update['id']),
			'timestamp' => gmdate('Y-m-d H:i:s'),
			'response' => $response
		));
		echo json_encode($response);
	}
	else {
		// mongoClient()->updates->delete(array('_id' => array('id' => $update['id'], 'db' => $databaseName)));
		echo json_encode($updateDoc['response']);
	}
}
else if ($_GET['schema']) {
	$schema = array(
		'version' => $databaseSchema['version'] ? $databaseSchema['version'] : 1,
		'models' => $databaseSchema['models']
	);

	foreach ($schema['models'] as &$modelSchema) {
		unset($modelSchema['storage']);
		if ($modelSchema['attributes']) {
			foreach ($modelSchema['attributes'] as &$attrSchema) {
				unset($attrSchema['storage']);
			}
		}
		if ($modelSchema['relationships']) {
			foreach ($modelSchema['relationships'] as &$relSchema) {
				unset($relSchema['storage']);
			}			
		}
	}

	echo json_encode($schema);
}
else if ($_GET['pull']) {
	// $clientDocument = mongoClient()->clients->findOne(array('_id' => $clientId));
	// mongoClient()->clients->update(array('_id' => $clientId), array('$unset' => array("updates.$databaseName" => 1)));
	echo json_encode($clientDocument['updates'][$databaseName]);
}
else if ($backup = $_POST['backup']) {
	$backup = json_decode($backup, true);
	if ($backup['data']) $backup = $backup['data'];

	foreach ($backup as $model => $changes) {
		$storageNames = schemaAllModelStorage($databaseSchema, $model);
		foreach ($storageNames as $storageName) {
			$storageConfig = schemaModelStorageConfig($databaseSchema, $model, $storageName);
			$storage = storageEngine($databaseSchema, $storageName);
			$storage->truncate($databaseSchema, $storageConfig, $model);
		}
	}

	executeUpdate($backup, $databaseSchema);
}
else if ($_GET['test']) {
	$model = 'Allergy';
	$id = '55a93de56b3e1311030041a7';

	var_dump(ancestors($databaseSchema, $model, $id));
}
