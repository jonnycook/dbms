<?php

require_once('includes/header.php');

$clientId = md5(rand());

$mongo = mongoClient();


$client = array(
	'subscribedTo' => array(),
	'registeredAt' => gmdate('Y-m-d H:i:s'),
);

if ($_GET['client']) {
	$client += json_decode($_GET['client'], true);
}

$mongo->clients->insert($client);

echo json_encode(array(
	'id' => $client['_id']->{'$id'}
));
