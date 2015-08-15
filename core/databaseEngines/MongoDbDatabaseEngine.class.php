<?php

require_once(__DIR__.'/DatabaseEngine.class.php');


class MongoDbDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
		$this->client = new MongoClient();
		$this->db = $this->client->{$config['db']};
	}

	private function document($collection, $id) {
		try {
			return $this->db->{$collection}->findOne(['_id' => $id]);			
		}
		catch (Exception $e) {
			// var_dump($e);
			return null;
		}
	}

	private function storageId(array $storageConfig, $modelId) {
		if ($storageConfig['id']['auto'] || !isset($storageConfig['id']['auto'])) {
			try {
				return new MongoId($modelId);
			}
			catch (Exception $e) {
				// var_dump($e);
				return null;
			}
		}
		else {
			return $modelId;
		}
	}

	private function modelId(array $storageConfig, $storageId) {
		if ($storageConfig['id']['auto'] || !isset($storageConfig['id']['auto'])) {
			return $storageId->{'$id'};
		}
		else {
			return $storageId;
		}
	}

	public function ids($model, array $storageConfig) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$ids = [];
		$cursor = $this->db->{$collection}->find();
		foreach ($cursor as $document) {
			$ids[] = $this->modelId($storageConfig, $document['_id']);
		}

		return $ids;
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}
		$document = $this->document($collection, $this->storageId($storageConfig, $id));
		return $document[$attrName];
	}

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		switch ($relSchema['type']) {
			case 'One':
				if ($storageConfig['collection']) {
					$collection = $storageConfig['collection'];
				}
				else {
					$collection = $model;
				}

				$document = $this->document($collection, $this->storageId($storageConfig, $id));
				$value = $document[$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $relName];
				return !!$value;

			case 'Many':
				if (!$this->embeddedStorage($schema, $model, $relName)) {
					$foreignKey = $relSchema['storage']['foreignKey'] ? $relSchema['storage']['foreignKey'] : $relSchema['inverseRelationship'];

					$relModelStorageConfig = schemaModelStorageConfig($schema, $relSchema['model']);

					if ($relModelStorageConfig['collection']) {
						$collection = $relModelStorageConfig['collection'];
					}
					else {
						$collection = $relSchema['model'];
					}

					$cursor = $this->db->{$collection}->find([($foreignKey) => $id]);
					foreach ($cursor as $document) {
						$value[] = $this->modelId($relModelStorageConfig, $document['_id']);
					}

					return !!$value;
				}
				else {
					if ($storageConfig['collection']) {
						$collection = $storageConfig['collection'];
					}
					else {
						$collection = $model;
					}
					$document = $this->document($collection, $this->storageId($storageConfig, $id));
					$value = $document[$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $relName];
					return !!$value;
				}
		}
	}

	private function embeddedStorage(array $schema, $model, $rel) {
		$relSchema = $schema['models'][$model]['relationships'][$rel];

		if ($relSchema['type'] == 'One' || $schema['models'][$relSchema['model']]['relationships'][$relSchema['inverseRelationship']]['type'] == 'Many' || $relSchema['type'] == 'Many' && !$relSchema['inverseRelationship']) {
			return true;
		}
		else {
			return false;
		}
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$data = [];
		if ($id !== null) {
			$data['_id'] = $this->storageId($storageConfig, $id);
		}

		if ($changes['attributes']) {
			$data += $changes['attributes'];
		}

		if ($relationships = $changes['relationships']) {
			foreach ($relationships as $name => $value) {
				$relSchema = $schema['models'][$model]['relationships'][$name];
				if ($this->embeddedStorage($schema, $model, $name)) {
					$data[$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $name] = $value;
				}
			}
		}

		$this->db->{$collection}->insert($data);
		return $this->modelId($storageConfig, $data['_id']);
	}



	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}


		if ($changes['attributes']) {
			$update = ['$set' => $changes['attributes']];		
		}

		if ($relationships = $changes['relationships']) {
			foreach ($relationships as $name => $value) {
				$relSchema = $schema['models'][$model]['relationships'][$name];
				if ($this->embeddedStorage($schema, $model, $name)) {
					$update['$set'][$relSchema['storage']['key'] ? $relSchema['storage']['key'] : $name] = $value;
				}
			}
		}

		if ($update) {
			$this->db->{$collection}->update(['_id' => $this->storageId($storageConfig, $id)], $update, ['upsert' => true]);			
		}



		if ($changes['operations']) {
			foreach ($changes['operations'] as $operation) {
				$update = null;
				switch ($operation['operation']) {
					case 'assign':
						$update['$set'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;

					case 'add':
					case 'push':
						$update['$push'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;

					case 'pop':
						$update['$pop'][implode('.', $operation['path'])] = 1;
						break;

					case 'shift':
						$update['$pop'][implode('.', $operation['path'])] = -1;
						break;

					case 'remove':
						$update['$pull'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;
				}

				if ($update) {
					$this->db->{$collection}->update(['_id' => $this->storageId($storageConfig, $id)], $update, ['upsert' => true]);			
				}

			}
		}
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$this->db->{$collection}->remove(['_id' => $this->storageId($storageConfig, $id)]);
	}

	public function truncate(array $schema, array $storageConfig, $model) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$this->db->{$collection}->drop();
	}
}
