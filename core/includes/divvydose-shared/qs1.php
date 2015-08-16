<?php

function qs1Insert($method, $fields) {
  $methodParts = explode('/', $method);

  $fieldsStr = array();

  foreach ($fields as $key => $value) {
    $fieldsStr[] = "$key=$value";
  }
  $fieldsStr = implode('&', $fieldsStr);

  if (defined('TESTING')) {
    $url = "http://sandbox.qs1api.com/api/$methodParts[0]/VendorTest";    
  }
  else {
    $url = "http://52.27.135.117/api/$methodParts[0]/divvyDOSE";    
  }
  if ($methodParts[1]) {
    $url .= "/$methodParts[1]";
  }

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
  $response = curl_exec($ch);

  return json_decode($response, true);
}



// TODO: cache expire
function qs1Get($method, $params=[]) {
	$methodParts = explode('/', $method);

	$fieldsStr = [];

	foreach ($params as $key => $value) {
	  $fieldsStr[] = "$key=$value";
	}
	$fieldsStr = implode('&', $fieldsStr);

  $url = "http://" . QS1_SERVER . "/api/$methodParts[0]/" . QS1_PHARMACY;    

	if ($methodParts[1]) {
	  $url .= "/$methodParts[1]";
	}

	if ($fieldsStr) {
		$url .= "?$fieldsStr";		
	}

	if (defined('QS1_USE_CACHE')) {
		$mongo = new MongoClient();
		$id = md5($url);
		$document = $mongo->divvydose->qs1Cache->findOne(['_id' => $id]);
		if ($document) {
			return $document['data'];
		}
		else {
			$data = json_decode(file_get_contents($url), true);
			$mongo->divvydose->qs1Cache->insert(['_id' => $id, 'data' => $data, 'url' => $url, 'method' => $method, 'params' => $params]);
			return $data;
		}
	}
	else {
		return json_decode(file_get_contents($url), true);
	}
}
