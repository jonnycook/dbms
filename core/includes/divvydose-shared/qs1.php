<?php

function qs1Insert($method, $fields) {
  $methodParts = explode('/', $method);

  $fieldsStr = [];

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

