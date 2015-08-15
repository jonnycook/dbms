<?php

require_once(__DIR__.'/DatabaseEngine.class.php');

class MysqlDatabaseStorageEngine extends DatabaseEngine {
	public function __construct(array $config) {
		mysql_connect($config['server'], $config['user'], $config['password']);
		mysql_select_db($config['db']);
	}

	private function record($table, $id) {
		return mysql_fetch_assoc(mysql_query("SELECT * FROM $table WHERE id = $id"));
	}

	public function resolveDep($dep, $config, $id) {
		return $this->record($config['table'], $id);
	}

	private function setPart(array $schema, $model, array $changes) {
		$setPart = [];
		if ($attributes = $changes['attributes']) {
			foreach ($attributes as $name => $value) {
				$value = mysql_real_escape_string($value);
				$values[$name] = $value;
			}
		}

		if ($relationships = $changes['relationships']) {
			foreach ($relationships as $name => $value) {
				$relSchema = $schema['models'][$model]['relationships'][$name];
				if ($relSchema['type'] == 'One') {
					$values[$relSchema['storage']['key']] = $value;
				}
			}
		}

		foreach ($values as $name => $value) {
			$setPart[] = "`$name` = '$value'";	
		}

		$setPart = implode(', ', $setPart);

		return $setPart;
	}

	private function query($sql) {
		$result = mysql_query($sql);
		if (!$result) {
			die(mysql_error());
		}
		return $result;
	}

	public function ids($model, array $storageConfig) {
		$result = $this->query("SELECT id FROM `$storageConfig[table]`");
		while ($row = mysql_fetch_array($result)) {
			$ids[] = $row['id'];
		}
		return $ids;
	}

	public function attribute($model, $id, array $storageConfig, $attrName, array $attrSchema) {
		$table = $storageConfig['table'];
		$record = $this->record($table, $id);
		return $record[$attrName];
	}

	public function relationship(array $schema, $model, $id, array $storageConfig, $relName, array $relSchema, &$value) {
		switch ($relSchema['type']) {
			case 'One':
				$table = $storageConfig['table'];
				$record = $this->record($table, $id);
				$value = $record[$relSchema['storage']['key']];
				if ($value !== null) {
					$value = intval($value);
				}
				return !!$value;

			case 'Many':
				$relModelStorageConfig = schemaModelStorageConfig($schema, $relSchema['model']);
				$table = $relModelStorageConfig['table'];

				$result = mysql_query("SELECT * FROM `$table` WHERE `{$relSchema['storage']['foreignKey']}` = $id");
				while ($row = mysql_fetch_assoc($result)) {
					$value[] = intval($row['id']);
				}

				return !!$value;
		}
	}


	public function update(array $schema, array $storageConfig, $model, $id, array $changes) {
		$setPart = $this->setPart($schema, $model, $changes);

		if ($setPart) {
			$escapedId = mysql_real_escape_string($id);
			$table = $storageConfig['table'];
			$this->query("UPDATE `$table` SET $setPart WHERE id = '$escapedId'");
 		}
	}

	public function insert(array $schema, array $storageConfig, $model, $id, array $changes) {
		$setPart = $this->setPart($schema, $model, $changes);

		if ($setPart) {
			$escapedId = mysql_real_escape_string($id);
			$table = $storageConfig['table'];
			$this->query("INSERT INTO `$table` SET $setPart");
			return mysql_insert_id();
 		}
	}

	public function delete(array $schema, array $storageConfig, $model, $id) {
		$table = $storageConfig['table'];
		$escapedId = mysql_real_escape_string($id);
		$this->query("DELETE FROM `$table` WHERE id = '$escapedId'");
	}
}

