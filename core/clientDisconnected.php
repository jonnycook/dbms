<?php

require_once('includes/header.php');

$mongo = mongoClient();

$clientDocument = $mongo->clients->findOne(array('_id' => new MongoId($_GET['id'])));

if ($clientDocument['opts']['terminateOnDisconnect']) {
	terminateClient($_GET['id']);
}
else {
	$mongo->clients->update(array('_id' => new MongoId($_GET['id'])), array('$set' => array('connected' => false)));
}