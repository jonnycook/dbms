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
		return array($obj);
	}
	else {
		$storage = storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $propSchema));
		return array($storage, modelSchemaStorageConfig(schemaModel($schema, $model), $storageName));
	}
}

function getObject(array $schema, $model, $id, &$results=null, $options=null) {
	// $storageType = schemaModelStorage($schema, $model);
	$attributes = schemaModelAttributes($schema, $model);
	$relationships = schemaModelRelationships($schema, $model);



	$object = array();
	foreach ($attributes as $name => $attrSchema) {
		if ($results[$model][$id][$name] || (isset($options['properties']) && !$options['properties'][$name])) continue;
		list($storage, $storageConfig) = propStorage($schema, $model, $name);//storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $attrSchema));

		$modelSchema = schemaModel($schema, $model);
		// $storageConfig = modelSchemaStorageConfig($modelSchema, $storageName);

		$deps = $storage->dependencies($model, $name);
		if ($deps) {
			$resolvedDeps = array();
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
			if (isset($options['properties']) && !$options['properties'][$relName]) continue;

			if ($value = $results[$model][$id][$relName]) {
				if ($options['getRelationships'] || !isset($options['getRelationships'])) {
					switch ($relSchema['type']) {
						case 'One':
							if (is_array($value)) {
								if (!$results[$value['model']][$value['id']]) {
									getObject($schema, $value['model'], $value['id'], $results);
								}
							}
							else {
								if (!$results[$relSchema['model']][$value]) {
									getObject($schema, $relSchema['model'], $value, $results);
								}
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
			else {
				$storage = storageEngine($schema, $storageName = propSchemaStorage($schema, $model, $relSchema));

				$modelSchema = schemaModel($schema, $model);
				$storageConfig = modelSchemaStorageConfig($modelSchema, $storageName);
				unset($value);

				if (!$relSchema['storage']['ignore']) {
					if ($storage->relationship($schema, $model, $id, $storageConfig, $relName, $relSchema, $value)) {
						if ($value !== null) {
							switch ($relSchema['type']) {
								case 'One':
									$object[$relName] = $value;
									if ($options['getRelationships'] || !isset($options['getRelationships'])) {
										if (is_array($value)) {
											if (!$results[$value['model']][$value['id']]) {
												getObject($schema, $value['model'], $value['id'], $results);
											}
										}
										else {
											if (!$results[$relSchema['model']][$value]) {
												getObject($schema, $relSchema['model'], $value, $results);
											}
										}
									}
									break;

								case 'Many':
									$ids = array();
									foreach ($value as $v) {
										if (is_array($v)) {
											$relId = $v['id'];
											unset($v['id']);
											$results[$relSchema['model']][$relId] = $v;
											if ($options['getRelationships'] || !isset($options['getRelationships'])) {
												getObject($schema, $relSchema['model'], $relId, $results);
											}
										}
										else {
											$relId = $v;
											if ($options['getRelationships'] || !isset($options['getRelationships'])) {
												if (!$results[$relSchema['model']][$relId]) {
													getObject($schema, $relSchema['model'], $relId, $results);
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
							$object[$relName] = array();
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
		$modelSchema['storage']['filter']($results[$model][$id]);
	}

	return $results[$model][$id];
}

function updateObject(array $schema, $model, $id, &$changes, &$mapping=null, array $allChanges=array(), $whatToUpdate='both') {
	assert(is_string($model));
	assert($changes !== null);
	if ($changes == 'delete') {
		if (isTemporaryId($id)) {
			return;
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


function addSubscriberToResource($db, $schemaVersion, $resource, $clientId) {
	$mongo = mongoClient();
	$id = array('db' => $db, 'resource' => $resource);
	$resourceDocument = $mongo->resources->findOne(array('_id' => $id));
	if ($resourceDocument) {
		if (!in_array($clientId, $resourceDocument['subscribers'])) {
			$mongo->resources->update(array('_id' => $id), array('$push' => array('subscribers' => $clientId)));
			$mongo->clients->update(array('_id' => new MongoId($clientId)), array('$push' => array('subscribedTo' => array('db' => $db, 'schemaVersion' => $schemaVersion, 'resource' => $resource))));
		}
	}
	else {
		$mongo->resources->insert(array('_id' => $id, 'subscribers' => array($clientId)));
		$mongo->clients->update(array('_id' => new MongoId($clientId)), array('$push' => array('subscribedTo' => array('db' => $db, 'schemaVersion' => $schemaVersion, 'resource' => $resource))));
	}
}

function distributeUpdate($db, $databaseSchema, $update, $clientId) {
	$clientChanges = array();
	foreach ($update['data'] as $model => $modelChanges) {
		foreach ($modelChanges as $id => $instanceChanges) {
			$lineage = ancestors($databaseSchema, $model, $id);
			foreach ($lineage as $instance) {
				$resourceDocument = mongoClient()->resources->findOne(array(
					'_id' => array(
						'db' => $db, 
						'resource' => array(
							'type' => 'model',
							'model' => $instance['model'],
							'id' => $instance['id'],
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

function executeUpdate($update, $databaseSchema) {
	$mapping = array();

	foreach ($update as $model => &$modelChanges) {
		$storage = storageEngine($databaseSchema, schemaModelStorage($databaseSchema, $model));
		if ($storage->singleInsert()) {
			foreach ($modelChanges as $id => &$changes) {
				if (isTemporaryId($id) && !$mapping[$id]) {
					updateObject($databaseSchema, $model, $id, $changes, $mapping, $update);
				}
			}
			unset($changes);
		}
		unset($update['model']);
	}
	unset($modelChanges);


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

	return array($mapping, $resolvedUpdate);
}

function sendToClient($clientId, $db, $update) {	
	mongoClient()->clients->update(array('_id' => new MongoId($clientId)), array('$push' => array("updates.$db" => $update)));

	$clientDocument = mongoClient()->clients->findOne(array('_id' => new MongoId($clientId)));


	if ($clientDocument['connected']) {
		httpPost('http://localhost:3001/push', array(
			'clientIds' => array($clientId),
			'update' => json_encode($update)
		));
	}
	// else {
	// }
}

function ancestors($databaseSchema, $model, $id) {
	$properties = array();
	foreach (schemaModelRelationships($databaseSchema, $model) as $name => $schema) {
		if ($schema['owner']) {
			$properties[$name] = true;
		}
	}

	$objects = array(
		array(
			'model' => $model,
			'id' => $id,
		)
	);
	if ($properties) {
		$object = getObject($databaseSchema, $model, $id, $results, array('getRelationships' => false, 'properties' => $properties));
		foreach (schemaModelRelationships($databaseSchema, $model) as $name => $schema) {
			if ($schema['owner']) {
				$objects = array_merge($objects, ancestors($databaseSchema, $schema['model'], $object[$name]));
			}
		}
	}
	return $objects;
}
