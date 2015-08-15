<?php

function schemaModel(array $schema, $model) {
	return $schema['models'][$model];
}

function schemaModelStorage(array $schema, $model) {
	if ($schema['models'][$model]['storage']['primary']) {
		return $schema['models'][$model]['storage']['primary'];
	}
	else {
		return $schema['databases']['default'];
	}
}

function schemaModelAttributes(array $schema, $model) {
	return (array)$schema['models'][$model]['attributes'];
}

function schemaModelRelationships(array $schema, $model) {
	return (array)$schema['models'][$model]['relationships'];
}

function schemaModelProperty(array $schema, $model, $property) {
	$modelSchema = schemaModel($schema, $model);
	$attributes = schemaModelAttributes($schema, $model);
	if ($propSchema = $attributes[$property]) {
		return $propSchema;
	}
	else {
		$relationships = schemaModelRelationships($schema, $model);
		return $relationships[$property];
	}
}

function schemaModelPropertyStorage(array $schema, $model, $property) {
	return propSchemaStorage($schema, $model, schemaModelProperty($schema, $model, $property));
}

function propSchemaStorage(array $schema, $model, array $attrSchema) {
	if ($attrSchema['storage']['db']) {
		return $attrSchema['storage']['db'];
	}
	else {
		return schemaModelStorage($schema, $model);
	}
}

function schemaStorageConfig(array $schema, $storage) {
	assert($storage);
	return $schema['databases'][$storage];
}

function storageConfigType(array $storageConfig) {
	return $storageConfig['type'];
}

function modelSchemaStorageConfig(array $modelSchema, $storageName) {
	return (array)$modelSchema['storage']['config'][$storageName];
}

function schemaModelStorageConfig(array $schema, $model, $storageName=null) {
	if (!$storageName) {
		$storageName = schemaModelStorage($schema, $model);
	}
	$modelSchema = schemaModel($schema, $model);
	return (array)modelSchemaStorageConfig($modelSchema, $storageName);
}


function schemaAllModelStorage($schema, $model) {
	$attributes = schemaModelAttributes($schema, $model);
	$relationships = schemaModelRelationships($schema, $model);
	$storageNames = [];
	foreach ($attributes as $attrName => $attrSchema) {
		$storageNames[propSchemaStorage($schema, $model, $attrSchema)] = true;
	}
	foreach ($relationships as $attrName => $attrSchema) {
		$storageNames[propSchemaStorage($schema, $model, $attrSchema)] = true;
	}

	return array_keys($storageNames);
}
