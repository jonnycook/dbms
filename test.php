<?php

// $ch = curl_init('http://sandbox.qs1api.com/api/Patient/VendorTest/Addresses?patientID=DEGESA');

// $fields = json_decode('
// {
//   "PatientID": "DEGESA",
//   "Name": "sample string 2",
//   "InCareOf": "sample string 3",
//   "Address": "sample string 4",
//   "Address2": "sample string 5",
//   "City": "sample string 6",
//   "State": "IA",
//   "Zip": "52556",
//   "PhoneNumber": "5555555555",
//   "POBox": "N",
//   "RefrigeratedItems": "Y",
//   "SpecialtyItem": "N",
//   "AddressID": "",
//   "OrderNo": -1,
//   "TotalAddresses": 1,
//   "AddressType": "",
//   "BeginDate": "00000000",
//   "EndDate": "00000000",
//   "InActive": "N",
//   "Notes": "sample string 18"
// }
// ', true);

// $fieldsStr = '';

// foreach ($fields as $key => $value) {
// 	$fieldsStr[] = "$key=$value";
// }
// $fieldsStr = implode('&', $fieldsStr);

// echo $fieldsStr;

// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsStr);
// curl_exec($ch);


$method = 'Patient/Profile?patientID=asdf';

$methodParts = explode('/', $method);

// $fieldsStr = [];

// foreach ($fields as $key => $value) {
//   $fieldsStr[] = "$key=$value";
// }
// $fieldsStr = implode('&', $fieldsStr);

  $url = "http://52.27.135.117/api/$methodParts[0]/divvyDOSE";    

if ($methodParts[1]) {
  $url .= "/$methodParts[1]";
}

var_dump($url);
