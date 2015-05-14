<?php

require_once('includes/header.php');

$mongo = mongoClient();
$clientId = $_GET['id'];
$db = $_GET['db'];
$mongo->clients->update(array('_id' => $clientId), array('$unset' => array("updates.$db" => 1)));
