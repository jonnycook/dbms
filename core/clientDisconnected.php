<?php

require_once('includes/header.php');

$mongo = mongoClient();

$clientDocument = $mongo->clients->findOne(['_id' => makeClientId($_GET['id'])]);

// if ($clientDocument['opts']['terminateOnDisconnect']) {
	// terminateClient($_GET['id']);
// }
// else {
	$mongo->clients->update(['_id' => makeClientId($_GET['id'])], ['$set' => ['connected' => false, 'disconnectedAt' => time()]]);
// }
