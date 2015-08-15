<?php

require_once('includes/header.php');

$mongo = mongoClient();
$result = $mongo->clients->update(['_id' => makeClientId($_GET['id'])], ['$set' => ['connected' => true]]);

if ($result['n'] == 0) {
	echo 'invalidClientId';
}
