<?php
//ini_set('html_errors', 0);
// header('Content-Type: text/plain');

require_once('includes/header.php');
require_once('includes/schema.php');
require_once('databaseEngines/MongoDbDatabaseEngine.class.php');
require_once('databaseEngines/MysqlDatabaseEngine.class.php');
require_once('databaseEngines/JsonDatabaseEngine.class.php');
require_once('databaseEngines/AddressesDatabaseEngine.class.php');
require_once('databaseEngines/InsuranceDatabaseEngine.class.php');
require_once('databaseEngines/MedicationsDatabaseEngine.class.php');
require_once('databaseEngines/PatientsDatabaseEngine.class.php');
require_once('databaseEngines/PaymentMethodsDatabaseEngine.class.php');

require_once('includes/main.php');


if ($_REQUEST['clientId']) {
	$clientId = $_REQUEST['clientId'];
}
$databaseName = $_REQUEST['db'];
if ($_REQUEST['schemaVersion']) {
	$schemaVersion = $_REQUEST['schemaVersion'];
}
else {
	$schemaVersion = 1;
}

$ravenClient->user_context(array('clientId' => $clientId));
$ravenClient->extra_context(array('schema' => $schemaVersion, 'env' => ENV));

$databaseSchema = require("databases/$databaseName.php");

if ($clientId) {
	if ($clientId == 'wW8tp9y1vp2Y8vH') {
		$clientDocument = [];
	}
	else {
		try {
			$id = makeClientId($clientId);
		}
		catch (Exception $e) {
			die('invalidClientId');
		}

		$clientDocument = mongoClient()->clients->findOne(['_id' => $id]);
		if (!$clientDocument) {
			die('invalidClientId');
		}

		mongoClient()->clients->update(['_id' => $id], ['$push' => ['requests' => ['get' => $_GET, 'post' => $_POST]]]);
	}
}

if ($databaseSchema['init']) {
	$databaseSchema['init']($clientDocument);
}

if ($resourceRoots = $_GET['resource']) {
	$params = [];

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

		if (preg_match("#^$pattern\$#", $resourceRoots, $matches)) {
			foreach ($paramNames as $i => $paramName) {
				$params[$paramName] = $matches[$i + 1];
			}
			$matched = true;
			break;
		}
	}

	if ($matched) {
		if (!$routeSchema[0]) {
			$routeSchemas = [$routeSchema];
		}
		else {
			$routeSchemas = $routeSchema;
		}
		$allResults = [];
		foreach ($routeSchemas as $routeSchema) {
			$results = [];
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

				$resolvedResource = [
					'type' => 'db',
				];
			}
			else if ($routeSchema['type'] == 'model') {
				if ($params['id']) {
					getObject($databaseSchema, $params['model'], $params['id'], $results, ['owner' => true, 'relationships' => []]);
					$resolvedResource = [
						'type' => 'model',
						'model' => $params['model'],
						'id' => $params['id'],
					];
				}
				else {
					$primaryStorageName = schemaModelStorage($databaseSchema, $params['model']);
					$storageConfig = schemaModelStorageConfig($databaseSchema, $params['model'], $primaryStorageName);
					$storage = storageEngine($databaseSchema, $primaryStorageName);

					$ids = $storage->ids($params['model'], $storageConfig);
					foreach ($ids as $id) {
						getObject($databaseSchema, $params['model'], $id, $results);
					}

					$resolvedResource = [
						'type' => 'model',
						'model' => $params['model'],
					];
				}
			}
			else if ($routeSchema['type'] == 'resource') {
				$id = $routeSchema['id']($clientDocument);

				resource($databaseSchema, $routeSchema['resource'], $id, $results);

				$resolvedResource = [
					'name' => $routeSchema['resource'],
					'id' => $id,
				];
			}

			if ($clientId && $resolvedResource) addSubscriberToResource($databaseName, $schemaVersion, $resolvedResource, $clientId);

			foreach ($results as $model => $instances) {
				foreach ($instances as $id => $instance) {
					$allResults[$model][$id] = $instance;
				}
				// $allResults[$model] = array_merge((array)$allResults[$model], $instances);
			}
		}

		// if ($clientId) {
		// 	addSubscriberToResources($databaseName, $schemaVersion, $clientId, $allResults);
		// }
		echo json_encode([
			'path' => $resourceRoots,
			'data' => $allResults
		] + $resolvedResource);
	}
}
else if ($update = $_POST['update']) {
	$update = json_decode($update, true);

	$updateDoc = mongoClient()->updates->findOne(['_id' => ['db' => $databaseName, 'id' => $update['id']]]);
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
			list($mapping, $resolvedUpdate, $errors) = executeUpdate($withoutDelete, $databaseSchema);
			if ($errors) {
				echo json_encode((object)array('errors' => $errors));
				exit;
			}
		}

		if ($delete) {
			foreach ($delete as $model => $modelChanges) {
				foreach ($modelChanges as $id => $instanceChanges) {
					$resolvedUpdate[$model][$id] = $instanceChanges;
				}
			}
		}

		distributeUpdate($databaseName, $databaseSchema, ['id' => $update['id'], 'data' => $resolvedUpdate], $clientId);

		if ($delete) {
			executeUpdate($delete, $databaseSchema);
		}

		$response = new stdClass();
		if ($mapping) {
			$response->mapping = $mapping;
		}

		mongoClient()->updates->insert([
			'_id' => ['db' => $databaseName, 'id' => $update['id']],
			'timestamp' => gmdate('Y-m-d H:i:s'),
			'response' => $response
		]);
		echo json_encode($response);
	}
	else {
		// mongoClient()->updates->delete(array('_id' => array('id' => $update['id'], 'db' => $databaseName)));
		echo json_encode($updateDoc['response']);
	}
}
else if ($_GET['schema']) {
	$schema = [
		'version' => $databaseSchema['version'] ? $databaseSchema['version'] : 1,
		'models' => $databaseSchema['models'],
		'resources' => $databaseSchema['resources'],
	];

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
	echo json_encode((array)$clientDocument['updates'][$databaseName]);
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
	var_dump(nodePaths($databaseSchema, 'user', 'User'));
	var_dump(resourceSubtreeOptions($databaseSchema, 'user', 'medicineLogEntries', [], ['excludeReferences' => true]));
//	var_dump(nodeEdges($databaseSchema, 'user', 'caringFor'));

//	var_dump(resources($databaseSchema, 'User', '55bcb30d77c870a477876611'));
//	var_dump(resources($databaseSchema, 'Address', '55862c576b3e13ec390041a8'));


//	var_dump(resources($databaseSchema, 'User', '55bcb30d77c870a477876611'));
//	var_dump(resources($databaseSchema, 'Address', '55862c576b3e13ec390041a8'));


//	var_dump(resource($databaseSchema, 'user', '55bc6e3677c8e917272f9c15'));

//	var_dump(resourceSubtree($databaseSchema, 'user', '', array('55bc6e3677c8e917272f9c15')));
	// var_dump(resourceSubtree($databaseSchema, 'user', '', array('55bc6e3677c8e917272f9c15'), $results, array('edges' => array('bills'))));


//	var_dump(resolvedResourcePaths($databaseSchema, 'user', 'caringFor.caredForUser.addresses', '55862c576b3e13ec390041a8'));
//	var_dump(resolvedResourcePaths($databaseSchema, 'user', 'caringFor.caredForUser', '55bcb30d77c870a477876611'));

//	var_dump(nodeInfo($databaseSchema, 'user', 'bills'));
//	var_dump(resolvePathInfo($databaseSchema, 'user', 'caringFor.caredForUser.addresses'));
//	var_dump(nodePaths($databaseSchema, 'user', 'User'));


//	$basePath = 'caringFor';
//	$id = '55bccc416b3e135dcf0041a7';
//	$model = 'Caregiver';
//
//	$basePath = '';
//	$id = '55bc6e3677c8e917272f9c15';
//	$model = $graphSchema['root'];
//
//	$basePath = '';
//	$id = '55bcb30d77c870a477876611';
//	$model = $graphSchema['root'];
//
//


}
