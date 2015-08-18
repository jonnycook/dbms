<?php

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('UTC');
require_once(__DIR__.'/env.php');

require_once(__DIR__.'/../vendor/autoload.php');

$ravenClient = new Raven_Client('https://49379cd047ce4c7686fac7ce7572fcf8:2a5739f68245414d9098922280f314a0@app.getsentry.com/50459', [
	'release' => '1.0.0',
]);

$error_handler = new Raven_ErrorHandler($ravenClient);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();

function _mongoClient() {
	global $_mongoClient;
	if (!$_mongoClient) {
		if (ENV == 'prod') {
			$mongoOpts = [
				'username' => 'admin', 
				'password' => 'I3B9632J87oz5dn',
			];
			$_mongoClient = new MongoClient("mongodb://localhost:27017", $mongoOpts);
		}
		else {
			$_mongoClient = new MongoClient();			
		}
	}
	return $_mongoClient;
}


function mongoClient() {
	$client = _mongoClient();
	return $client->dbms;
}

function terminateClient($clientId, $opts=[]) {
	$mongo = mongoClient();
	$clientDocument = $mongo->clients->findOne(['_id' => makeClientId($clientId)]);
	if ($clientDocument['subscribedTo']) {
		foreach ($clientDocument['subscribedTo'] as $resource) {
			unset($resource['schemaVersion']);
			$mongo->resources->update(['_id' => $resource], ['$pull' => ['subscribers' => $clientId]]);
		}
	}

	$data = ['connected' => false, 'terminated' => gmdate('Y-m-d H:i:s')];
	if ($opts['replaced']) $data['replaced'] = true;
	$mongo->clients->update(['_id' => makeClientId($clientId)], ['$set' => $data]);
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
