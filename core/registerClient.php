<?php

require_once('includes/header.php');
require_once('includes/divvydose-shared/encryption.php');
require_once('includes/CryptoLib.php');

$clientId = CryptoLib::randomString(32);

$mongo = mongoClient();


$client = array(
	'_id' => $clientId,
	'subscribedTo' => array(),
	'registeredAt' => gmdate('Y-m-d H:i:s'),
);

if ($_GET['client']) {
	$client += json_decode($_GET['client'], true);
}

if ($client['token']) {
	$client['userId'] = decrypt($client['token'], 'USER_ID');
}

$mongo->clients->insert($client);

echo json_encode(array(
	'id' => $clientId,
));
