<?php

require_once('includes/header.php');

$clientId = md5(rand());

$mongo = mongoClient();

if ($_GET['terminateOnDisconnect']) {
	$opts['terminateOnDisconnect'] = true;
}

if ($_GET['params']) {
	$params = json_decode($_GET['params']);
}

if ($_GET['device']) {
	$device = json_decode($_GET['device']);
}

$mongo->clients->insert(array(
	'_id' => $clientId,
	'token' => $_GET['token'],
	'device' => $device,
	'opts' => $opts,
	'subscribedTo' => array(),
	'params' => $params,
	'registeredAt' => gmdate('Y-m-d H:i:s'),
));

echo json_encode(array(
	'id' => $clientId
));
