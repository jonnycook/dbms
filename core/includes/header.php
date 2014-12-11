<?php

require_once(__DIR__.'/env.php');

function mongoClient() {
	global $_mongoClient;
	if (!$_mongoClient) {
		$_mongoClient = new MongoClient();
	}
	return $_mongoClient->dbms;
}

function terminateClient($clientId) {
	$mongo = mongoClient();
	$clientDocument = $mongo->clients->findOne(array('_id' => $clientId));
	if ($clientDocument['subscribedTo']) {
		foreach ($clientDocument['subscribedTo'] as $resource) {
			$mongo->resources->update(array('_id' => $resource), array('$pull' => array('subscribers' => $clientId)));
		}
	}

	$mongo->clients->remove(array('_id' => $clientId));
}

function httpPost($url, $data) {
	$options = array(
	    'http' => array(
	        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	        'method'  => 'POST',
	        'content' => http_build_query($data),
	    ),
	);
	$context  = stream_context_create($options);
	return file_get_contents($url, false, $context);
}