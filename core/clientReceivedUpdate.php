<?php

require_once('includes/header.php');

$mongo = mongoClient();
$clientId = $_GET['id'];
$db = $_GET['db'];
$result = $mongo->clients->update(['_id' => makeClientId($clientId)], ['$unset' => ["updates.$db" => 1]]);

if ($result['n'] == 0) {
	echo 'invalidClientId';
}
