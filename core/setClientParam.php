<?php

require_once('includes/header.php');

$mongo = mongoClient();
$clientId = $_GET['id'];
$mongo->clients->update(array('_id' => $clientId), array('$set' => array("params.$_GET[key]" => $_GET['value'])));
