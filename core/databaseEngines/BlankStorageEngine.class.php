<?php

require_once(__DIR__.'/DatabaseEngine.class.php');


class BlankDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
	}

	public function ids($model, array $storageConfig) {
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
	}

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
	}

	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
	}

	public function truncate(array $schema, array $storageConfig, $model) {
	}
}
