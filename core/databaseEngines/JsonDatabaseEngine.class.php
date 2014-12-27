<?php

require_once(__DIR__.'/DatabaseEngine.class.php');



class JsonDatabaseStorageEngine extends DatabaseEngine {
	private static function mapObject($rules, $object, $state, &$output) {
		if (is_array($rules)) {
			foreach ($rules as $ruleKey => $ruleValue) {
				if ($ruleValue === null) {
					$output[] = $state;
					return null;
				}
				else if ($ruleKey[0] == '@') {
					$objectProp = substr($ruleKey, 1);
					if ($objectProp == '*') {
						$result = array();
						foreach ($object as $key => $value) {
							$result[] = self::mapObject($ruleValue, $value, $state, $output);
						}
						return $result;
					}
					else {
						return self::mapObject($ruleValue, $object[$objectProp], $state, $output);
					}
				}
				else if ($ruleKey == '*') {
					$result = self::mapObject($ruleValue, $object, $state, $output);
					foreach ($result as $key => $value) {
						if (!isset($state[$key])) {
							$state[$key] = $value;
						}
					}
				}
				else {
					$state[$ruleKey] = self::mapObject($ruleValue, $object, $state, $output);
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


	public function __construct(array $config) {
		$this->object = json_decode(file_get_contents($config['file']), true);
		$this->config = $config;
	}

	private function initModel($model, array $storageConfig) {
		if (!$this->models[$model]) {
			self::mapObject($storageConfig, $this->object, array(), $output);

			if ($this->config['attributeNameMapping']) {
				$from = array_keys($this->config['attributeNameMapping'])[0];
				$to = $this->config['attributeNameMapping'][$from];
			}

			foreach ($output as $obj) {
				$newObj = array();
				if ($from) {
					foreach ($obj as $key => $value) {
						if ($from == 'underscores') {
							$parts = explode('_', $key);
						}						

						if ($to == 'camelCase') {
							$key = lcfirst(implode('', array_map(ucfirst, $parts)));
						}

						$newObj[$key] = $value;
					}
				}
				$obj = $newObj;
				$id = $obj['id'];
				unset($obj['id']);
				$newOutput[$id] = $obj;
			}
			$output = $newOutput;

			$this->models[$model]	= $output;
		}
	}

	public function ids($model, array $storageConfig) {
		$this->initModel($model, $storageConfig);
		return array_keys($this->models[$model]);
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
		$this->initModel($model, $storageConfig);

		return $this->models[$model][$id][$attrName];
	}

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		$this->initModel($model, $storageConfig);
		$value = $this->models[$model][$id][$relName];
		return !!$value;
	}

	// public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
	// 	if ($storageConfig['collection']) {
	// 		$collection = $storageConfig['collection'];
	// 	}
	// 	else {
	// 		$collection = $model;
	// 	}

	// 	$data = array();
	// 	if ($id !== null) {
	// 		$data['_id'] = $this->storageId($storageConfig, $id);
	// 	}

	// 	if ($changes['attributes']) {
	// 		$data += $changes['attributes'];
	// 	}

	// 	if ($relationships = $changes['relationships']) {
	// 		foreach ($relationships as $name => $value) {
	// 			$relSchema = $schema['models'][$model]['relationships'][$name];
	// 			if ($relSchema['type'] == 'One') {
	// 				$data[$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $name] = $value;
	// 			}
	// 		}
	// 	}

	// 	$this->db->{$collection}->insert($data);
	// 	return $this->modelId($storageConfig, $data['_id']);
	// }

	// public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
	// 	if ($storageConfig['collection']) {
	// 		$collection = $storageConfig['collection'];
	// 	}
	// 	else {
	// 		$collection = $model;
	// 	}


	// 	if ($changes['attributes']) {
	// 		$update = array('$set' => $changes['attributes']);		
	// 	}

	// 	if ($relationships = $changes['relationships']) {
	// 		foreach ($relationships as $name => $value) {
	// 			$relSchema = $schema['models'][$model]['relationships'][$name];
	// 			if ($relSchema['type'] == 'One') {
	// 				$update['$set'][$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $name] = $value;
	// 			}
	// 		}
	// 	}

	// 	if ($changes['operations']) {
	// 		foreach ($changes['operations'] as $operation) {
	// 			switch ($operation['operation']) {
	// 				case 'assign':
	// 					$update['$set'][implode('.', $operation['path'])] = $operation['parameters'][0];
	// 					break;

	// 				case 'add':
	// 				case 'push':
	// 					$update['$push'][implode('.', $operation['path'])] = $operation['parameters'][0];
	// 					break;

	// 				case 'pop':
	// 					$update['$pop'][implode('.', $operation['path'])] = 1;
	// 					break;

	// 				case 'shift':
	// 					$update['$pop'][implode('.', $operation['path'])] = -1;
	// 					break;

	// 				case 'remove':
	// 					$update['$pull'][implode('.', $operation['path'])] = $operation['parameters'][0];
	// 					break;

	// 			}
	// 		}
	// 	}

	// 	if ($update) {
	// 		$this->db->{$collection}->update(array('_id' => $this->storageId($storageConfig, $id)), $update, array('upsert' => true));			
	// 	}
	// }

	// public function delete(array $schema, array $storageConfig, $model, $id) {
	// 	if ($storageConfig['collection']) {
	// 		$collection = $storageConfig['collection'];
	// 	}
	// 	else {
	// 		$collection = $model;
	// 	}

	// 	$this->db->{$collection}->remove(array('_id' => $this->storageId($storageConfig, $id)));
	// }
}
