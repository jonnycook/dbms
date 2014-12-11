<?php

require_once('includes/header.php');

$mongo = mongoClient();
$mongo->clients->update(array('_id' => $_GET['id']), array('$set' => array('connected' => true)));
