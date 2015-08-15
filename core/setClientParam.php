<?php

require_once('includes/header.php');

$mongo = mongoClient();

$clientId = $_GET['id'];
$key = $_GET['key'];
$value = $_GET['value'];

$set = [$key => $value];

if ($key == 'token') {
	if ($value) {
		require_once('includes/divvydose-shared/encryption.php');
		$set['userId'] = decrypt($value, 'USER_ID');
	}
	else {
		$set['userId'] = null;
	}
}

$result = $mongo->clients->update(['_id' => makeClientId($clientId)], ['$set' => $set]);

if ($result['n'] == 0) {
	echo 'invalidClientId';
}
