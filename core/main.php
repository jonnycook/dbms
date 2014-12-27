<?php

require_once('includes/header.php');
require_once('includes/schema.php');
require_once('databaseEngines/MongoDbDatabaseEngine.class.php');
require_once('databaseEngines/MysqlDatabaseEngine.class.php');
require_once('databaseEngines/JsonDatabaseEngine.class.php');

header('Content-Type: text/plain');

function createStorageEngine($type, array $config) {
	switch ($type) {
		case 'mysql': $class = 'MysqlDatabaseStorageEngine'; break;
		case 'mongodb': $class = 'MongoDbDatabaseStorageEngine'; break;
		case 'json': $class = 'JsonDatabaseStorageEngine'; break;
		default: throw new Exception("Invalid storage engine $type");
	}

	if ($class) {
		return new $class($config);
	}
}
function storageEngine(array $schema, $name) {
	global $_storageEngines;
	if ($_storageEngines[$name]) {
		return $_storageEngines[$name];
	}
	else {
		$storageConfig = schemaStorageConfig($schema, $name);
		$storageType = storageConfigType($storageConfig);
		return $_storageEngines[$name] = createStorageEngine($storageType, $storageConfig);		
	}
}


function isTemporaryId($id) {
	return $id[0] == '$';
}

function getObject(array $schema, $model, $id, &$results=null) {
	// $storageType = schemaModelStorage($schema, $model);
	$attributes = schemaModelAttributes($schema, $model);
	$relationships = schemaModelRelationships($schema, $model);

	$object = array();
	foreach ($attributes as $name => $attrSchema) {
		$storage = storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $attrSchema));

		$modelSchema = schemaModel($schema, $model);
		$storageConfig = modelSchemaStorageConfig($modelSchema, $storageName);

		$object[$name] = $storage->attribute($model, $id, $storageConfig, $name, $attrSchema);
	}

	$results[$model][$id] = true;

	foreach ($relationships as $relName => $relSchema) {
		$storage = storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $relSchema));

		$modelSchema = schemaModel($schema, $model);
		$storageConfig = modelSchemaStorageConfig($modelSchema, $storageName);

		unset($value);
		if ($storage->relationship($schema, $model, $id, $storageConfig, $relName, $relSchema, $value)) {
			$object[$relName] = $value;
			if ($value !== null) {
				switch ($relSchema['type']) {
					case 'One':
						if (!$results[$relSchema['model']][$value]) {
							getObject($schema, $relSchema['model'], $value, $results);					
						}
						break;

					case 'Many':
						foreach ($value as $relId) {
							if (!$results[$relSchema['model']][$relId]) {
								getObject($schema, $relSchema['model'], $relId, $results);
							}
						}
						break;
				}
			}
		}
	}

	$results[$model][$id] = $object;

	return $object;
}

function updateObject(array $schema, $model, $id, &$changes, &$mapping=null, array $allChanges=array(), $whatToUpdate='both') {
	assert(is_string($model));
	assert($changes !== null);
	if ($changes == 'delete') {
		$storageNames = schemaAllModelStorage($schema, $model);
		foreach ($storageNames as $storageName) {
			$storageConfig = schemaModelStorageConfig($schema, $model, $storageName);
			$storage = storageEngine($schema, $storageName);
			$storage->delete($schema, $storageConfig, $model, $id);
		}
	}
	else {
		$attributes = schemaModelAttributes($schema, $model);
		$relationships = schemaModelRelationships($schema, $model);
		foreach ($changes as $prop => &$value) {
			if (preg_match('/^([^.(\[]+)([.(\[].+)$/', $prop, $matches)) {
				$value = (array)$value;

				$prop = $matches[1];

				if ($whatToUpdate == 'attributes' && $relationships[$prop] || $whatToUpdate == 'relationships' && $attributes[$prop]) continue;
				$storageName = schemaModelPropertyStorage($schema, $model, $prop);

				$str = $matches[2];

				if (preg_match('/(\[[\^$]?\]|\(\))$/', $str, $matches)) {
					$op = $matches[1];
					$pathStr = substr($str, 0, -strlen($op));


					switch ($op) {
						case '[]':
						case '[$]':
							$operation = 'push';
							break;

						case '[^]':
							$operation = 'unshift';
							break;

						case '()':
							$operation = array_shift($value);
							break;
					}
				}
				else {
					$operation = 'assign';
					$pathStr = $str;
				}

				$globalMatches = null;
				preg_match_all('/\.([^\[.]+)|\[([^\]]+)\]/', $pathStr, $globalMatches);

				$path = array($prop);
				if ($globalMatches) {
					for ($i = 0; $i < count($globalMatches[0]); ++ $i) {
						if ($p = $globalMatches[1][$i]) {
							$path[] = $p;
						}
						else {
							$path[] = $globalMatches[2][$i];
						}
					}
				}

				if ($relSchema = $relationships[$prop]) {
					foreach ($value as &$relId) {
						if (isTemporaryId($relId)) {
							if ($mapping[$relId]) {
								$relId = $mapping[$relId];
							}
							else {
								$relId = updateObject($schema, $relSchema['model'], $relId, $allChanges[$relSchema['model']][$relId], $mapping, $allChanges);
							}
						}
					}
					unset($relId);
				}

				$storageChanges[$storageName]['operations'][] = array(
					'operation' => $operation,
					'parameters' => $value,
					'path' => $path
				);
			}
			else {
				if ($whatToUpdate == 'attributes' && $relationships[$prop] || $whatToUpdate == 'relationships' && $attributes[$prop]) continue;
				if ($attrSchema = $attributes[$prop]) {
					$storageName = propSchemaStorage($schema, $model, $attrSchema);
					$storageChanges[$storageName]['attributes'][$prop] = $value;
				}
				else {
					$relSchema = $relationships[$prop];
					$storageName = propSchemaStorage($schema, $model, $relSchema);

					if ($relSchema['type'] == 'One') {
						if (isTemporaryId($value)) {
							if ($mapping[$value]) {
								$value = $mapping[$value];
							}
							else {
								$value = updateObject($schema, $relSchema['model'], $value, $allChanges[$relSchema['model']][$value], $mapping, $allChanges);
							}
						}
						$storageChanges[$storageName]['relationships'][$prop] = $value;
					}
					else if ($relSchema['type'] == 'Many') {
						foreach ($value as &$relId) {
							if (isTemporaryId($relId)) {
								if ($mapping[$relId]) {
									$relId = $mapping[$relId];
								}
								else {
									$relId = updateObject($schema, $relSchema['model'], $relId, $allChanges[$relSchema['model']][$relId], $mapping, $allChanges);
								}
							}
						}
						unset($relId);
						$storageChanges[$storageName]['relationships'][$prop] = $value;
					}
				}
			}
		}
		unset($value);

		if (isTemporaryId($id)) {
			$primaryStorageName = schemaModelStorage($schema, $model);
			$storageConfig = schemaModelStorageConfig($schema, $model, $primaryStorageName);
			$storage = storageEngine($schema, $primaryStorageName);
			$mapping[$id] = $storage->insert($schema, $storageConfig, $model, null, (array)$storageChanges[$primaryStorageName]);
			unset($storageChanges[$primaryStorageName]);

			if ($storageChanges) {
				foreach ($storageChanges as $storageName => $c) {
					$storageConfig = schemaModelStorageConfig($schema, $model, $storageName);
					$storage = storageEngine($schema, $storageName);
					$storage->insert($schema, $storageConfig, $model, $mapping[$id], $c);
				}
			}

			return $mapping[$id];
		}
		else {
			if ($storageChanges) {
				foreach ($storageChanges as $storageName => $c) {
					$storageConfig = schemaModelStorageConfig($schema, $model, $storageName);
					$storage = storageEngine($schema, $storageName);
					$storage->update($schema, $storageConfig, $model, $id, $c);
				}				
			}
		}
	}
}

$databaseName = $_GET['db'];
$databaseSchema = require("databases/$databaseName.php");
$clientId = $_GET['clientId'];

function addSubscriberToResource($db, $resource, $clientId) {
	$mongo = mongoClient();
	$id = array('db' => $db, 'resource' => $resource);
	$resourceDocument = $mongo->resources->findOne(array('_id' => $id));
	if ($resourceDocument) {
		if (!in_array($clientId, $resourceDocument['subscribers'])) {
			$mongo->resources->update(array('_id' => $id), array('$push' => array('subscribers' => $clientId)));
			$mongo->clients->update(array('_id' => $clientId), array('$push' => array('subscribedTo' => array('db' => $db, 'resource' => $resource))));
		}
	}
	else {
		$mongo->resources->insert(array('_id' => $id, 'subscribers' => array($clientId)));
		$mongo->clients->update(array('_id' => $clientId), array('$push' => array('subscribedTo' => array('db' => $db, 'resource' => $resource))));
	}
}

function distributeUpdate($db, $update, $clientId) {
	$resourceDocument = mongoClient()->resources->findOne(array('_id' => array('db' => $db, 'resource' => array('type' => 'db'))));
	if ($resourceDocument['subscribers']) {
		foreach ($resourceDocument['subscribers'] as $subscriber) {
			if ($subscriber != $clientId) {
				sendToClient($subscriber, $db, $update);
			}
		}
	}
}

function sendToClient($clientId, $db, $update) {
	$clientDocument = mongoClient()->clients->findOne(array('_id' => $clientId));
	if ($clientDocument['connected']) {
		httpPost('http://localhost:3001/push', array(
			'clientIds' => array($clientId),
			'update' => json_encode($update)
		));
	}
	else {
		mongoClient()->clients->update(array('_id' => $clientId), array('$push' => array("updates.$db" => $update)));
	}
}

if ($resource = $_GET['resource']) {
	$models = array_keys($databaseSchema['models']);
	foreach ($databaseSchema['routes'] as $route => $routeSchema) {
		preg_match_all('/:([a-z]+)/', $route, $matches);
		$paramNames = $matches[1];
		$params = array();

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
		if ($routeSchema['type'] == 'db') {
			foreach ($databaseSchema['models'] as $modelName => $modelSchema) {
				$primaryStorageName = schemaModelStorage($databaseSchema, $modelName);
				$storageConfig = schemaModelStorageConfig($databaseSchema, $modelName, $primaryStorageName);
				$storage = storageEngine($databaseSchema, $primaryStorageName);

				$ids = $storage->ids($modelName, $storageConfig);
				foreach ($ids as $id) {
					getObject($databaseSchema, $modelName, $id, $results);
				}
			}

			$resolvedResource = array(
				'type' => 'db',
			);
		}
		else if ($routeSchema['type'] == 'model') {
			getObject($databaseSchema, $params['model'], $params['id'], $results);
			$resolvedResource = array(
				'type' => 'model',
				'model' => $params['model'],
				'id' => $params['id'],
			);
		}

		echo json_encode(array(
			'path' => $resource,
			'data' => $results
		) + $resolvedResource);

		addSubscriberToResource($databaseName, $resolvedResource, $clientId);
	}
}
else if ($update = $_POST['update']) {
	$update = json_decode($update, true);
	$mapping = array();

	foreach ($update as $model => &$modelChanges) {
		foreach ($modelChanges as $id => &$changes) {
			if (isTemporaryId($id) && !$mapping[$id]) {
				updateObject($databaseSchema, $model, $id, $changes, $mapping, $update, 'attributes');
			}
		}
		unset($changes);
	}
	unset($modelChanges);

	foreach ($update as $model => &$modelChanges) {
		foreach ($modelChanges as $id => &$changes) {
			if (isTemporaryId($id)) {
				updateObject($databaseSchema, $model, $mapping[$id], $changes, $mapping, $update, 'relationships');
			}
		}
		unset($changes);
	}
	unset($modelChanges);

	foreach ($update as $model => &$modelChanges) {
		foreach ($modelChanges as $id => &$changes) {
			if (!isTemporaryId($id)) {
				updateObject($databaseSchema, $model, $id, $changes, $mapping, $update);
			}
		}
		unset($changes);
	}
	unset($modelChanges);


	foreach ($update as $model => $modelChanges) {
		foreach ($modelChanges as $id => $changes) {
			if ($newId = $mapping[$id]) {
				$resolvedUpdate[$model][$newId] = $changes;
			}
			else {
				$resolvedUpdate[$model][$id] = $changes;
			}
		}
	}

	distributeUpdate($databaseName, $resolvedUpdate, $clientId);

	$response = new stdClass();
	if ($mapping) {
		$response->mapping = $mapping;
	}
	echo json_encode($response);
}
else if ($_GET['schema']) {
	$schema = array(
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
	$clientDocument = mongoClient()->clients->findOne(array('_id' => $clientId));
	mongoClient()->clients->update(array('_id' => $clientId), array('$unset' => array("updates.$databaseName" => 1)));
	echo json_encode($clientDocument['updates'][$databaseName]);
}
