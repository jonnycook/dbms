<?php

require_once('includes/header.php');

$mongo = mongoClient();
$result = $mongo->clients->update(array('_id' => new MongoId($_GET['id'])), array('$set' => array('connected' => true)));

if ($result['n'] == 0) {
	echo 'invalidClientId';
}
