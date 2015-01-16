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

$mongo->clients->insert(array('_id' => $clientId, 'opts' => $opts, 'subscribedTo' => array(), 'params' => $params));

echo json_encode(array(
	'id' => $clientId
));
