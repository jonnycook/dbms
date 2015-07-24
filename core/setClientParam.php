<?php

require_once('includes/header.php');

$mongo = mongoClient();
$clientId = $_GET['id'];
$result = $mongo->clients->update(array('_id' => $clientId), array('$set' => array("params.$_GET[key]" => $_GET['value'])));

if ($result['n'] == 0) {
	echo 'invalidClientId';
}

