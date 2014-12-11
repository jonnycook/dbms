<?php

require_once('includes/header.php');

$mongo = mongoClient();

$clientDocument = $mongo->clients->findOne(array('_id' => $_GET['id']));

if ($clientDocument['opts']['terminateOnDisconnect']) {
	terminateClient($_GET['id']);
}
else {
	$mongo->clients->update(array('_id' => $_GET['id']), array('$set' => array('connected' => false)));
}