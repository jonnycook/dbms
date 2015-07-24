<?php

require_once('includes/header.php');

$mongo = mongoClient();
$result = $mongo->clients->update(array('_id' => $_GET['id']), array('$set' => array('connected' => true)));

echo json_encode($result['n'] > 0);
