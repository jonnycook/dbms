<?php

require_once('includes/header.php');
require_once('includes/divvydose-shared/encryption.php');
require_once('includes/CryptoLib.php');

$clientId = CryptoLib::randomString(32);

$mongo = mongoClient();


$client = [
	'_id' => $clientId,
	'subscribedTo' => [],
	'registeredAt' => gmdate('Y-m-d H:i:s'),
	'ip' => $_SERVER['REMOTE_ADDR'],
];

if ($_GET['client']) {
	$client += json_decode($_GET['client'], true);
}

if ($client['token']) {
	$client['userId'] = decrypt($client['token'], 'USER_ID');
}

if ($client['type'] == 'mobile') {
	$cursor = $mongo->clients->find([
		'type' => 'mobile', 
		'device.id' => $client['device']['id'],
		'device.platform' => $client['device']['platform'],
		'terminated' => ['$exists' => false],
		'app' => $client['app'],
	]);
	foreach ($cursor as $otherClient) {
		terminateClient($otherClient['_id'], ['replaced' => true]);
	}
}

// $ravenClient->captureMessage(json_encode($client));

$mongo->clients->insert($client);

echo json_encode([
	'id' => $clientId,
]);
