<?php

require_once('includes/header.php');

$clientId = md5(rand());

$mongo = mongoClient();

if ($_GET['terminateOnDisconnect']) {
	$opts['terminateOnDisconnect'] = true;
}

$mongo->clients->insert(array('_id' => $clientId, 'opts' => $opts, 'subscribedTo' => array()));

echo json_encode(array(
	'id' => $clientId
));
