<?php

function def($value, $default) {
	if (!$value) return $default;
	return $value;
}

function createStorageEngine($type, array $config) {
	switch ($type) {
		case 'mysql': $class = 'MysqlDatabaseStorageEngine'; break;
		case 'mongodb': $class = 'MongoDbDatabaseStorageEngine'; break;
		case 'json': $class = 'JsonDatabaseStorageEngine'; break;
		case 'addresses': $class = 'AddressesDatabaseStorageEngine'; break;
		case 'insurance': $class = 'InsuranceDatabaseStorageEngine'; break;
		case 'medications': $class = 'MedicationsDatabaseStorageEngine'; break;
		case 'patients': $class = 'PatientsDatabaseStorageEngine'; break;
		case 'paymentMethods': $class = 'PaymentMethodsStorageEngine'; break;
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

class ComputeStorage {
	public function __construct($opts) {
		$this->opts = $opts;
	}
	public function attribute($model, $id, $storageConfig, $name, $attrSchema, $deps) {
		return call_user_func_array($this->opts['compute'], $deps);
	}

	public function dependencies() {
		return $this->opts['dependencies'];
	}

	public function resolveDep($dep, $config, $id) {

	}
}

function propStorage($schema, $model, $prop) {
	$propSchema = schemaModelProperty($schema, $model, $prop);
	if ($propSchema['storage']['compute']) {
		$obj = new ComputeStorage($propSchema['storage']);
		return [$obj];
	}
	else {
		$storage = storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $propSchema));
		return [$storage, modelSchemaStorageConfig(schemaModel($schema, $model), $storageName)];
	}
}

function getObject(array $schema, $model, $id, &$results=null, $options=null) {
	$attributes = schemaModelAttributes($schema, $model);
	$relationships = schemaModelRelationships($schema, $model);

	$object = [];
	foreach ($attributes as $name => $attrSchema) {
		if ($results[$model][$id][$name] || (isset($options['properties']) && !$options['properties'][$name])) continue;
		list($storage, $storageConfig) = propStorage($schema, $model, $name);//storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $attrSchema));

		$modelSchema = schemaModel($schema, $model);
		// $storageConfig = modelSchemaStorageConfig($modelSchema, $storageName);

		$deps = $storage->dependencies($model, $name);
		if ($deps) {
			$resolvedDeps = [];
			foreach ($deps as $i => $dep) {
				list($db, $dbProp) = explode('.', $dep);
				$s = storageEngine($schema, $db);
				$c = modelSchemaStorageConfig($modelSchema, $db);
				$resolvedDeps[$i] = $s->resolveDep($dbProp, $c, $id);
			}
		}

		$object[$name] = $storage->attribute($model, $id, $storageConfig, $name, $attrSchema, $resolvedDeps);
	}


	if (!$results[$model][$id]) {
		$first = true;
		$results[$model][$id] = true;
	}

	foreach ($relationships as $relName => $relSchema) {
		// TODO: this is a huge hack!!!
		if ($relSchema['access'] == 'owner' && $options['child']) continue;

		if (isset($options['properties']) && !$options['properties'][$relName] || isset($options['relationships']) && !$options['relationships'][$relName] || $options['excludeProperties'][$relName]) continue;


		$relOptions = (array)$options['propertyOptions'][$relName] + (array)$relSchema['storage']['objectOptions'];

		$relOptions += [
			'child' => true,
			'from' => ['model' => $model, 'id' => $id, 'relName' => $relName],
		];

		// var_dump($relName, $relOptions);


		if ($value = $results[$model][$id][$relName]) {
			if ($options['getRelationships'] || !isset($options['getRelationships'])) {
				switch ($relSchema['type']) {
					case 'One':
						if (is_array($value)) {
							if (!$results[$value['model']][$value['id']]) {
								getObject($schema, $value['model'], $value['id'], $results, $relOptions);
							}
						}
						else {

							if ($options['excludeRelationshipInstances'][$relName] && in_array($value, $options['excludeRelationshipInstances'][$relName])) break;

							if (!$results[$relSchema['model']][$value]) {
								getObject($schema, $relSchema['model'], $value, $results, $relOptions);
							}
						}
						break;

					case 'Many':
						foreach ($value as $relId) {
							if (($options['excludeRelationshipInstances'][$relName] && in_array($relId, $options['excludeRelationshipInstances'][$relName]))) continue;
							if (!$results[$relSchema['model']][$relId]) {
								getObject($schema, $relSchema['model'], $relId, $results, $relOptions);
							}
						}
						break;
				}
			}
		}
		else {
			$storage = storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $relSchema));

			$modelSchema = schemaModel($schema, $model);
			$storageConfig = modelSchemaStorageConfig($modelSchema, $storageName);
			unset($value);

			if (!$relSchema['storage']['ignore']) {
				// var_dump($relName);
				if ($storage->relationship($schema, $model, $id, $storageConfig, $relName, $relSchema, $value)) {
					if ($value !== null) {
						switch ($relSchema['type']) {
							case 'One':
								$object[$relName] = $value;
								if ($options['getRelationships'] || !isset($options['getRelationships'])) {
									if (is_array($value)) {
										if (!$results[$value['model']][$value['id']]) {
											getObject($schema, $value['model'], $value['id'], $results, $relOptions);
										}
									}
									else {
										if (!$results[$relSchema['model']][$value]) {
											if ($options['excludeRelationshipInstances'][$relName] && in_array($value, $options['excludeRelationshipInstances'][$relName])) continue 2;
											getObject($schema, $relSchema['model'], $value, $results, $relOptions);
										}
									}
								}
								break;

							case 'Many':
								$ids = [];
								foreach ($value as $v) {
									if (is_array($v)) {
										$relId = $v['id'];
										unset($v['id']);
										$results[$relSchema['model']][$relId] = $v;
										if ($options['getRelationships'] || !isset($options['getRelationships'])) {
											getObject($schema, $relSchema['model'], $relId, $results, $relOptions);
										}
									}
									else {
										$relId = $v;
										if ($options['getRelationships'] || !isset($options['getRelationships'])) {
											if (!$results[$relSchema['model']][$relId]) {
												if ($options['excludeRelationshipInstances'][$relName] && in_array($relId, $options['excludeRelationshipInstances'][$relName])) continue;

												getObject($schema, $relSchema['model'], $relId, $results, $relOptions);
											}
										}
									}
									$ids[] = $relId;
								}
								$object[$relName] = $ids;
								break;
						}
					}
					else {
						$object[$relName] = $value;
					}
				}
			}
			else {
				switch ($relSchema['type']) {
					case 'One':
						$object[$relName] = null;
						break;

					case 'Many':
						$object[$relName] = [];
						break;
				}
			}
		}
	}
	
	if ($results[$model][$id] === true) {
		$results[$model][$id] = $object;
	}
	else {
		$results[$model][$id] = array_merge((array)$results[$model][$id], $object);
	}

	$modelSchema = schemaModel($schema, $model);

	if ($modelSchema['storage']['filter'] && $first) {
		$modelSchema['storage']['filter']($results[$model][$id], $id);
	}

	return $results[$model][$id];
}

function updateObject(array $schema, $model, $id, &$changes, &$results=null, array $allChanges=[], $whatToUpdate='both') {
	assert(is_string($model));
	assert($changes !== null);
	if ($changes == 'delete') {
		if (isTemporaryId($id)) {
			// TODO: Possibly response with error
			return null;
		}
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

				if (preg_match('/(\[[\^$]?\]|\([^(]*\))$/', $str, $matches)) {
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

						default:
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

				$path = [$prop];
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
							if ($results['mapping'][$relId]) {
								$relId = $results['mapping'][$relId];
							}
							else {
								$relId = updateObject($schema, $relSchema['model'], $relId, $allChanges[$relSchema['model']][$relId], $results, $allChanges);
							}
						}
					}
					unset($relId);
				}

				$storageChanges[$storageName]['operations'][] = [
					'operation' => $operation,
					'parameters' => $value,
					'path' => $path
				];
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
							if ($results['mapping'][$value]) {
								$value = $results['mapping'][$value];
							}
							else {
								// TODO: Handle errors
								$value = updateObject($schema, $relSchema['model'], $value, $allChanges[$relSchema['model']][$value], $results, $allChanges);
							}
						}
						$storageChanges[$storageName]['relationships'][$prop] = $value;
					}
					else if ($relSchema['type'] == 'Many') {
						foreach ($value as &$relId) {
							if (isTemporaryId($relId)) {
								if ($results['mapping'][$relId]) {
									$relId = $results['mapping'][$relId];
								}
								else {
									// TODO: Handle errors
									$relId = updateObject($schema, $relSchema['model'], $relId, $allChanges[$relSchema['model']][$relId], $results, $allChanges);
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
			$r = $storage->insert($schema, $storageConfig, $model, null, (array)$storageChanges[$primaryStorageName]);
			if ($r !== false) {
				$results['mapping'][$id] = $r;
				unset($storageChanges[$primaryStorageName]);

				if ($storageChanges) {
					foreach ($storageChanges as $storageName => $c) {
						$storageConfig = schemaModelStorageConfig($schema, $model, $storageName);
						$storage = storageEngine($schema, $storageName);
						$storage->insert($schema, $storageConfig, $model, $results['mapping'][$id], $c);
					}
				}

				return $results['mapping'][$id];
			}
			else {
				$results['errors'][$model][$id] = $storage->errors;
				return false;
			}
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


function addSubscriberToResource($db, $schemaVersion, $resource, $clientId) {
	$mongo = mongoClient();
	$id = ['db' => $db, 'resource' => $resource];
	$resourceDocument = $mongo->resources->findOne(['_id' => $id]);
	if ($resourceDocument) {
		if (!in_array($clientId, $resourceDocument['subscribers'])) {
			$mongo->resources->update(['_id' => $id], ['$push' => ['subscribers' => $clientId]]);
			$mongo->clients->update(['_id' => makeClientId($clientId)], ['$push' => ['subscribedTo' => ['db' => $db, 'schemaVersion' => $schemaVersion, 'resource' => $resource]]]);
		}
	}
	else {
		$mongo->resources->insert(['_id' => $id, 'subscribers' => [$clientId]]);
		$mongo->clients->update(['_id' => makeClientId($clientId)], ['$push' => ['subscribedTo' => ['db' => $db, 'schemaVersion' => $schemaVersion, 'resource' => $resource]]]);
	}
}

function addSubscriberToResources($db, $schemaVersion, $clientId, $resources) {
	foreach ($resources as $modelName => $instances) {
		foreach ($instances as $id => $instance) {
			addSubscriberToResource($db, $schemaVersion, ['type' => 'model', 'model' => $modelName, 'id' => $id], $clientId);
		}
	}
}


/*
function distributeUpdate($db, $databaseSchema, $update, $clientId) {
	$clientChanges = array();
	foreach ($update['data'] as $model => $modelChanges) {
		foreach ($modelChanges as $id => $instanceChanges) {
			$resourceDocument = mongoClient()->resources->findOne(array(
				'_id' => array(
					'db' => $db, 
					'resource' => array(
						'type' => 'model',
						'model' => $model,
						'id' => $id,
					),
				)
			));
			if ($resourceDocument['subscribers']) {
				foreach ($resourceDocument['subscribers'] as $subscriber) {
					if ($subscriber != $clientId) {
						$clientChanges[$subscriber][$model][$id] = $instanceChanges;					
					}
				}	
			}
		}
	}

	// var_dump($clientChanges);

	foreach ($clientChanges as $clientId => $changes) {
		sendToClient($clientId, $db, array('id' => $update['id'], 'data' => $changes));
	}

	// $resourceDocument = mongoClient()->resources->findOne(array('_id' => array('db' => $db, 'resource' => array('type' => 'db'))));
	// if ($resourceDocument['subscribers']) {
	// 	foreach ($resourceDocument['subscribers'] as $subscriber) {
	// 		if ($subscriber != $clientId) {
	// 			sendToClient($subscriber, $db, $update);
	// 		}
	// 	}
	// }
}

*/

class DbUtil {
	public function __construct($db, $schema) {
		$this->db = $db;
		$this->schema = $schema;
	}

	public function resolveRel($model, $id, $prop) {
		$results = [];
		if (is_array($prop)) {
			foreach ($prop as $p) {
				$results = array_merge($results, $this->resolveRel($model, $id, $p));
			}
		}
		else {
			$dot = strpos($prop, '.');
			if ($dot === false) {
				$obj = getObject($this->schema, $model, $id, $_, ['getRelationships' => false, 'properties' => [$prop => true]]);
				// var_dump($obj);

				$relModel = $this->schema['models'][$model]['relationships'][$prop]['model'];
				if (is_array($obj[$prop])) {
					foreach ($obj[$prop] as $relId) {
						$results[] = ['model' => $relModel, 'id' => $relId];
					}
				}
				else if ($obj[$prop]) {
					$results[] = ['model' => $relModel, 'id' => $obj[$prop]];
				}
			}
			else {
				$p = substr($prop, 0, $dot);
				$rest = substr($prop, $dot + 1);
				$relModel = $this->schema['models'][$model]['relationships'][$p]['model'];
				$obj = getObject($this->schema, $model, $id, $_, ['getRelationships' => false, 'properties' => [$p => true]]);
				// var_dump($obj);

				if (is_array($obj[$p])) {
					foreach ($obj[$p] as $relId) {
						$results = array_merge($results, $this->resolveRel($relModel, $relId, $rest));
					}
				}
				else if ($obj[$p]) {
					$results = $this->resolveRel($relModel, $obj[$p], $rest);
				}
			}
		}

		return $results;
	}

	public function subscribers($instances) {
		$clientIds = [];

		foreach ($instances as $instance) {
			$resourceDocument = mongoClient()->resources->findOne([
				'_id' => [
					'db' => $this->db, 
					'resource' => [
						'type' => 'model',
						'model' => $instance['model'],
						'id' => $instance['id'],
					],
				]
			]);

			if ($resourceDocument['subscribers']) {
				$clientIds = array_merge($clientIds, $resourceDocument['subscribers']);
			}
		}

		return array_unique($clientIds);
	}
}

//function distributeUpdate($db, $databaseSchema, $update, $clientId) {
//	$dbUtil = new DbUtil($db, $databaseSchema);
//
//	$clientChanges = array();
//	foreach ($update['data'] as $model => $modelChanges) {
//		foreach ($modelChanges as $id => $instanceChanges) {
//			$storage = $databaseSchema['models'][$model]['storage'];
//			if ($storage['distributeUpdate']) {
//				$clientIds = $storage['distributeUpdate']($dbUtil, $id, $model, $id);
//			}
//
//			$lineage = ancestors($databaseSchema, $model, $id);
//
//			foreach ($lineage as $instance) {
//				$storage = $databaseSchema['models'][$instance['model']]['storage'];
//
//				if ($storage['distributeUpdate']) {
//					$clientIds = $storage['distributeUpdate']($dbUtil, $instance['id'], $model, $id);
//				}
//
//				$resourceDocument = mongoClient()->resources->findOne(array(
//					'_id' => array(
//						'db' => $db,
//						'resource' => array(
//							'type' => 'model',
//							'model' => $instance['model'],
//							'id' => $instance['id'],
//						),
//					)
//				));
//
//				$clientIds = array_merge((array)$clientIds, (array)$resourceDocument['subscribers']);
//
//				if ($clientIds) {
//					foreach ($clientIds as $subscriber) {
//						if ($subscriber != $clientId) {
//							$clientChanges[$subscriber][$model][$id] = $instanceChanges;
//						}
//					}
//				}
//			}
//		}
//	}
//
//	foreach ($clientChanges as $clientId => $changes) {
//		sendToClient($clientId, $db, array('id' => $update['id'], 'data' => $changes));
//	}
//
//	// $resourceDocument = mongoClient()->resources->findOne(array('_id' => array('db' => $db, 'resource' => array('type' => 'db'))));
//	// if ($resourceDocument['subscribers']) {
//	// 	foreach ($resourceDocument['subscribers'] as $subscriber) {
//	// 		if ($subscriber != $clientId) {
//	// 			sendToClient($subscriber, $db, $update);
//	// 		}
//	// 	}
//	// }
//}


function resourceSubscribers($db, $resource, $id, $clientId = null) {
	$resourceDocument = mongoClient()->resources->findOne([
		'_id' => [
			'db' => $db,
			'resource' => [
				'name' => $resource,
				'id' => $id,
			],
		]
	]);

	$subscribers = (array)$resourceDocument['subscribers'];
	if ($clientId) {
		$index = array_search($clientId, $subscribers);
		if ($index !== false) {
			array_splice($subscribers, $index, 1);
		}
	}
	return $subscribers;
}

function distributeUpdate($db, $databaseSchema, $update, $clientId) {
	$clientChanges = [];
	foreach ($update['data'] as $model => $modelChanges) {
		foreach ($modelChanges as $id => $instanceChanges) {
			$resources = resources($databaseSchema, $model, $id);

			foreach ($resources as $resource) {
				$subscribers = resourceSubscribers($db, $resource['resource'], $resource['id'], $clientId);

				if ($subscribers) {
					$results = [];
					$results[$model][$id] = $instanceChanges;
					if ($instanceChanges != 'delete') {
						$edges = nodeEdges($databaseSchema, $resource['resource'], $resource['path']);
						$includedEdges = [];
						foreach ($edges as $edge) {
							if ($instanceChanges[$edge]) {
								$includedEdges[] = $edge;
							}
						}

						resourceSubtree($databaseSchema, $resource['resource'], $resource['path'], $resource['resolvedPath'], $results, ['edges' => $includedEdges, 'excludeReferences' => true]);
					}
					
					foreach ($subscribers as $subscriber) {
						$clientChanges[$subscriber] = $results;
					}
				}
			}
		}
	}

	foreach ($clientChanges as $cid => $changes) {
		sendToClient($cid, $db, ['id' => $update['id'], 'data' => $changes]);
	}
}

function executeUpdate($update, $databaseSchema) {
	$results = [];

	foreach ($update as $model => &$modelChanges) {
		$storage = storageEngine($databaseSchema, schemaModelStorage($databaseSchema, $model));
		if ($storage->singleInsert()) {
			foreach ($modelChanges as $id => &$changes) {
				if (isTemporaryId($id) && !$results['mapping'][$id]) {
					if (updateObject($databaseSchema, $model, $id, $changes, $results, $update) === false) {
						return [null, null, $results['errors']];
					}
				}
			}
			unset($changes);
		}
		unset($update['model']);
	}
	unset($modelChanges);

	foreach ($update as $model => &$modelChanges) {
		foreach ($modelChanges as $id => &$changes) {
			if (isTemporaryId($id) && !$results['mapping'][$id]) {
				if (updateObject($databaseSchema, $model, $id, $changes, $results, $update, 'attributes') === false) {
					return [null, null, $results['errors']];
				}
			}
		}
		unset($changes);
	}
	unset($modelChanges);

	foreach ($update as $model => &$modelChanges) {
		foreach ($modelChanges as $id => &$changes) {
			if (isTemporaryId($id)) {
				updateObject($databaseSchema, $model, $results['mapping'][$id], $changes, $results, $update, 'relationships');
			}
		}
		unset($changes);
	}
	unset($modelChanges);

	foreach ($update as $model => &$modelChanges) {
		foreach ($modelChanges as $id => &$changes) {
			if (!isTemporaryId($id)) {
				updateObject($databaseSchema, $model, $id, $changes, $results, $update);
			}
		}
		unset($changes);
	}
	unset($modelChanges);


	foreach ($update as $model => $modelChanges) {
		foreach ($modelChanges as $id => $changes) {
			if ($newId = $results['mapping'][$id]) {
				$resolvedUpdate[$model][$newId] = $changes;
			}
			else {
				$resolvedUpdate[$model][$id] = $changes;
			}
		}
	}

	$r = [$results['mapping'], $resolvedUpdate];
	if ($results['errors']) {
		$r[] = $results['errors'];
	}
	return $r;
}

function sendToClient($clientId, $db, $update) {	
	mongoClient()->clients->update(['_id' => makeClientId($clientId)], ['$push' => ["updates.$db" => $update]]);

	$clientDocument = mongoClient()->clients->findOne(['_id' => makeClientId($clientId)]);

	if ($clientDocument['connected']) {
		httpPost('http://localhost:3001/push', [
			'clientIds' => [$clientId],
			'update' => json_encode($update)
		]);
	}
}


function ancestors($databaseSchema, $model, $id) {
	$properties = [];
	foreach (schemaModelRelationships($databaseSchema, $model) as $name => $schema) {
		if ($schema['owner']) {
			$properties[$name] = true;
		}
	}

	$objects = [
		[
			'model' => $model,
			'id' => $id,
		]
	];
	if ($properties) {
		$object = getObject($databaseSchema, $model, $id, $results, ['getRelationships' => false, 'properties' => $properties]);
		foreach (schemaModelRelationships($databaseSchema, $model) as $name => $schema) {
			if ($schema['owner']) {
				$objects = array_merge($objects, ancestors($databaseSchema, $schema['model'], $object[$name]));
			}
		}
	}
	return $objects;
}

function ancestors2($databaseSchema, $model, $id) {
	$properties = [];
	foreach (schemaModelRelationships($databaseSchema, $model) as $name => $schema) {
		if ($schema['owner']) {
			$properties[$name] = true;
		}
	}

	$objects = [
		[
			'model' => $model,
			'id' => $id,
		]
	];
	if ($properties) {
		$object = getObject($databaseSchema, $model, $id, $results, ['getRelationships' => false, 'properties' => $properties]);

		foreach (schemaModelRelationships($databaseSchema, $model) as $name => $schema) {
			if ($schema['owner']) {
				$objects = array_merge($objects, ancestors($databaseSchema, $schema['model'], $object[$name]));
			}
		}
	}
	return $objects;
}

// resources
function nodeInfo($databaseSchema, $resource, $path) {
	$schema = $databaseSchema['resources'][$resource];
	$model = $schema['root'];

	if ($path) {
		$p = '';
		$components = explode('.', $path);
		for ($i = 0; $i < count($components); ++ $i) {
			$edge = $components[$i];
			if (!($schema['nodes'][$p]['edges'] !== false && $schema['nodes'][$p]['edges'][$edge] !== false && !in_array($edge, (array)$schema['models'][$model]['references']))) {
				return;
			}
			$model = $databaseSchema['models'][$model]['relationships'][$edge]['model'];
			if (!$model) return;
			$p = implode('.', array_slice($components, 0, $i + 1));
		}

	}

	return ['model' => $model, 'schema' => (array)$schema['nodes'][$path] + (array)$schema['models'][$model]];
}

function resolvePathInfo($databaseSchema, $resource, $path) {
	$schema = $databaseSchema['resources'][$resource];
	$model = $schema['root'];

	$pathInfo = [];

	if ($path) {
		$p = '';
		$components = explode('.', $path);
		for ($i = 0; $i < count($components); ++ $i) {
			$edge = $components[$i];
			if (!($schema['nodes'][$p]['edges'] !== false && $schema['nodes'][$p]['edges'][$edge] !== false && !in_array($edge, (array)$schema['models'][$model]['references']))) {
				return false;
			}
			$pathInfo[] = ['model' => $model, 'edge' => $edge];
			$model = $databaseSchema['models'][$model]['relationships'][$edge]['model'];
			if (!$model) return false;
			$p = implode('.', array_slice($components, 0, $i + 1));
		}

	}

	$pathInfo[] = ['model' => $model];

	return $pathInfo;
}


function nodePaths($databaseSchema, $resource, $model, $path = []) {
	$paths = [];

	$schema = $databaseSchema['resources'][$resource];
	if ($model == $schema['root']) {
		if (nodeInfo($databaseSchema, $resource, implode('.', $path))) {
			$paths[] = implode('.', $path);
		}
		else {
			return null;
		}
	}

	foreach ($databaseSchema['models'][$model]['relationships'] as $name => $relSchema) {
		if ($name == $path[0]) continue;
		if ($relSchema['inverseRelationship'] && ($relSchema['owner'] || in_array($name, (array)$schema['models'][$model]['edges']))) {
			$paths = array_merge($paths, (array)nodePaths($databaseSchema, $resource, $relSchema['model'], array_merge((array)$relSchema['inverseRelationship'], $path)));
		}
	}

	return $paths;
}

function resolvedResourcePaths($databaseSchema, $resource, $path, $id) {
	if ($path === '') {
		return [/*array('id' => $id, 'model' => $databaseSchema['resources'][$resource]['root'])*/[$id]];
	}

	$resolvedPath = resolvePathInfo($databaseSchema, $resource, $path);

	$stack[] = [
		'id' => $id,
		'model' => $resolvedPath[count($resolvedPath) - 1]['model'],
		'component' => count($resolvedPath) - 2,
		'resolvedPath' => [
			/*array('id' => $id, 'model' => $resolvedPath[count($resolvedPath) - 1]['model'])*/$id
		]
	];

	$roots = [];
	while ($s = array_pop($stack)) {
		$i = $s['component'];
		$model = $s['model'];
		$id = $s['id'];

		$component = $resolvedPath[$i];
		if ($component['edge']) {
			$rel = $databaseSchema['models'][$component['model']]['relationships'][$component['edge']]['inverseRelationship'];
			$object = getObject($databaseSchema, $model, $id, $results, ['getRelationships' => false, 'properties' => [$rel => true]]);

			if ($object) {
				if ($i > 0) {
					foreach ((array)$object[$rel] as $id) {
						$stack[] = [
							'id' => $id,
							'model' => $component['model'],
							'component' => $i - 1,
							'resolvedPath' => array_merge([/*array('id' => $id, 'model' => $component['model'], 'edge' => $component['edge'])*/$id], $s['resolvedPath']),
						];
					}
				}
				else {
					foreach ((array)$object[$rel] as $rootId) {
						$roots[] = array_merge([/*array('id' => $rootId, 'model' => $component['model'], 'edge' => $component['edge'])*/$rootId], $s['resolvedPath']);
					}
//						$roots = array_merge($roots, (array)$object[$rel]);
				}
			}
		}
		else {
			$roots[] = /*array('id' => $id, 'model' => $model)*/[$id];
		}
	}
	return $roots;
}

function resources($databaseSchema, $model, $id) {
	$resources = [];
	foreach ($databaseSchema['resources'] as $name => $_) {
		$paths = nodePaths($databaseSchema, $name, $model);
		foreach ($paths as $path) {
			$resolvedPaths = resolvedResourcePaths($databaseSchema, $name, $path, $id);

			foreach ($resolvedPaths as $resolvedPath) {
				$resources[] = [
					'resource' => $name,
					'path' => $path,
					'resolvedPath' => $resolvedPath,
					'id' => $resolvedPath[0],//['id'],
				];
			}
		}
	}
	return $resources;
}

function resource($databaseSchema, $resource, $id, &$results) {
	return resourceSubtree($databaseSchema, $resource, '', [$id], $results);
}

function resourceSubtreeOptions($databaseSchema, $resource, $basePath, $instancePath, $opts=[]) {
	$schema = $databaseSchema['resources'][$resource];

	$options = [];

	foreach ($schema['nodes'] as $path => $node) {
		if ($basePath) {
			if (substr($path, 0, strlen($basePath)) != $basePath) continue;
			$path = substr($path, strlen($basePath) + 1);

		}

		$obj = &$options;

		if ($path) {
			$components = explode('.', $path);
			foreach ($components as $component) {
				$obj = &$obj['propertyOptions'][$component];
			}
		}

		if ($node['edges'] === false) {
			$obj['relationships'] = [];
		}
		else if (isset($node['edges'])) {
			foreach ($node['edges'] as $edge => $include) {
				if (!$include) {
					$obj['excludeProperties'][$edge] = true;
				}
			}
		}

		if ($opts['excludeReferences'] && isset($node['references'])) {
			foreach ($node['references'] as $ref) {
				$obj['excludeProperties'][$ref] = true;
			}
		}
		unset($obj);
	}

	$resolvedPath = resolvePathInfo($databaseSchema, $resource, $basePath);
	$model = $resolvedPath[count($resolvedPath) - 1]['model'];
	$parentEdge = $databaseSchema['models'][$resolvedPath[count($resolvedPath) - 2]['model']]['relationships'][$resolvedPath[count($resolvedPath) - 2]['edge']]['inverseRelationship'];


	if ($parentEdge) {
		$options['excludeRelationshipInstances'][$parentEdge] = [$instancePath[count($instancePath) - 2]];
	}


	if (isset($opts['edges'])) {
		$edges = nodeEdges($databaseSchema, $resource, $basePath);
		foreach ($edges as $edge) {
			if (!in_array($edge, $opts['edges'])) {
				$options['excludeProperties'][$edge] = true;
			}
		}
	}

	return [$model, $options];
}

function resourceSubtree($databaseSchema, $resource, $basePath, $instancePath, &$results, $opts=[]) {
	// $schema = $databaseSchema['resources'][$resource];

	// $options = array();

	// foreach ($schema['nodes'] as $path => $node) {
	// 	if ($basePath) {
	// 		if (substr($path, 0, strlen($basePath)) != $basePath) continue;
	// 		$path = substr($path, strlen($basePath) + 1);

	// 	}

	// 	$obj = &$options;

	// 	if ($path) {
	// 		$components = explode('.', $path);
	// 		foreach ($components as $component) {
	// 			$obj = &$obj['propertyOptions'][$component];
	// 		}
	// 	}

	// 	if ($node['edges'] === false) {
	// 		$obj['relationships'] = array();
	// 	}
	// 	else if ($node['edges']) {
	// 		foreach ($node['edges'] as $edge => $include) {
	// 			if (!$include) {
	// 				$obj['excludeProperties'][$edge] = true;
	// 			}
	// 		}
	// 	}

	// 	if ($opts['excludeReferences'] && $node['references']) {
	// 		foreach ($node['references'] as $ref) {
	// 			$obj['excludeProperties'][$ref] = true;
	// 		}
	// 	}
	// 	unset($obj);
	// }

	// $resolvedPath = resolvePathInfo($databaseSchema, $resource, $basePath);
	// $model = $resolvedPath[count($resolvedPath) - 1]['model'];
	// $parentEdge = $databaseSchema['models'][$resolvedPath[count($resolvedPath) - 2]['model']]['relationships'][$resolvedPath[count($resolvedPath) - 2]['edge']]['inverseRelationship'];


	// if ($parentEdge) {
	// 	$options['excludeRelationshipInstances'][$parentEdge] = array($instancePath[count($instancePath) - 2]);
	// }


	// if ($opts['edges']) {
	// 	$edges = nodeEdges($databaseSchema, $resource, $basePath);
	// 	foreach ($edges as $edge) {
	// 		if (!in_array($edge, $opts['edges'])) {
	// 			$options['excludeProperties'][$edge] = true;
	// 		}
	// 	}
	// }

	// var_dump($options);

	// return;

	// if ($opts['ignoreReferences']) {
	// 	$nodeInfo = nodeInfo($databaseSchema, $resource, $path);

	// 	foreach ($)
	// }

//	if ($debug) {
//		var_dump($basePath, $instancePath, $options);
//	}

	list($model, $options) = resourceSubtreeOptions($databaseSchema, $resource, $basePath, $instancePath, $opts);

	getObject($databaseSchema, $model, $instancePath[count($instancePath) - 1], $results, $options);

	return $results;
}

function nodeEdges($databaseSchema, $resource, $path) {
	$nodeInfo = nodeInfo($databaseSchema, $resource, $path);
	$edges = [];
	foreach ($databaseSchema['models'][$nodeInfo['model']]['relationships'] as $relName => $relSchema) {
		if (!in_array($relName, (array)$nodeInfo['schema']['references'])) {
			$edges[] = $relName;
		}
	}
	return $edges;
}
