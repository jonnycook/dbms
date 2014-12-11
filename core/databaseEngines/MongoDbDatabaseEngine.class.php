<?php

require_once(__DIR__.'/DatabaseEngine.class.php');


class MongoDbDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
		$this->client = new MongoClient();
		$this->db = $this->client->{$config['db']};
	}

	private function document($collection, $id) {
		return $this->db->{$collection}->findOne(array('_id' => $id));
	}

	private function storageId(array $storageConfig, $modelId) {
		if ($storageConfig['id']['auto'] || !isset($storageConfig['id']['auto'])) {
			return new MongoId($modelId);
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

		$ids = array();
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
				$value = $document[$relSchema['storage']['key']];
				return !!$value;

			case 'Many':
				$relModelStorageConfig = schemaModelStorageConfig($schema, $relSchema['model']);

				if ($relModelStorageConfig['collection']) {
					$collection = $relModelStorageConfig['collection'];
				}
				else {
					$collection = $relSchema['model'];
				}

				$cursor = $this->db->{$collection}->find(array($relSchema['storage']['foreignKey'] => $id));
				foreach ($cursor as $document) {
					$value[] = $this->modelId($relModelStorageConfig, $document['_id']);
				}

				return !!$value;
		}
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$data = array();
		if ($id !== null) {
			$data['_id'] = $this->storageId($storageConfig, $id);
		}

		if ($changes['attributes']) {
			$data += $changes['attributes'];
		}

		if ($relationships = $changes['relationships']) {
			foreach ($relationships as $name => $value) {
				$relSchema = $schema['models'][$model]['relationships'][$name];
				if ($relSchema['type'] == 'One') {
					$data[$relSchema['storage']['key']] = $value;
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

		$update = array('$set' => $changes['attributes']);

		if ($relationships = $changes['relationships']) {
			foreach ($relationships as $name => $value) {
				$relSchema = $schema['models'][$model]['relationships'][$name];
				if ($relSchema['type'] == 'One') {
					$update['$set'][$relSchema['storage']['key']] = $value;
				}
			}
		}

		if ($changes['operations']) {
			foreach ($changes['operations'] as $operation) {
				switch ($operation['operation']) {
					case 'assign':
						$update['$set'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;

					case 'push':
						$update['$push'][implode('.', $operation['path'])] = $operation['parameters'][0];
						break;

					case 'pop':
						$update['$pop'][implode('.', $operation['path'])] = 1;
						break;

					case 'shift':
						$update['$pop'][implode('.', $operation['path'])] = -1;
						break;
				}
			}
		}

		$this->db->{$collection}->update(array('_id' => $this->storageId($storageConfig, $id)), $update, array('upsert' => true));
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
		if ($storageConfig['collection']) {
			$collection = $storageConfig['collection'];
		}
		else {
			$collection = $model;
		}

		$this->db->{$collection}->remove(array('_id' => $this->storageId($storageConfig, $id)));
	}
}
