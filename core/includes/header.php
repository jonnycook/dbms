<?php

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('UTC');
require_once(__DIR__.'/env.php');

function _mongoClient() {
	global $_mongoClient;
	if (!$_mongoClient) {
		$_mongoClient = new MongoClient();
	}
	return $_mongoClient;
}


function mongoClient() {
	$client = _mongoClient();
	return $client->dbms;
}

function terminateClient($clientId) {
	$mongo = mongoClient();
	$clientDocument = $mongo->clients->findOne(['_id' => makeClientId($clientId)]);
	if ($clientDocument['subscribedTo']) {
		foreach ($clientDocument['subscribedTo'] as $resource) {
			unset($resource['schemaVersion']);
			$mongo->resources->update(['_id' => $resource], ['$pull' => ['subscribers' => $clientId]]);
		}
	}

	$mongo->clients->remove(['_id' => makeClientId($clientId)]);
}

function httpPost($url, $data) {
	$options = [
    'http' => [
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data),
    ],
	];
	$context  = stream_context_create($options);
	return file_get_contents($url, false, $context);
}


function makeClientId($id) {
	return $id;
}

function array_merge_recursive_distinct ( array &$array1, array &$array2 )
{
	$merged = $array1;

	foreach ( $array2 as $key => &$value )
	{
		if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
		{
			$merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
		}
		else
		{
			$merged [$key] = $value;
		}
	}

	return $merged;
}
