<?php

require_once('includes/header.php');

$mongo = mongoClient();
$clientId = $_GET['id'];
$result = $mongo->clients->update(array('_id' => new MongoId($clientId)), array('$set' => array("$_GET[key]" => $_GET['value'])));

if ($result['n'] == 0) {
	echo 'invalidClientId';
}

