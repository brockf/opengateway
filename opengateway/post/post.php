<?php

require_once('xml.php');



$post_array = array('apiID' => '1234567890',
					'secretKey' => '0987654321');


$xml = new xml();

// Set the array so the class knows what to create the XML from
$xml->setArray($post_array);

// Print the XML to screen
$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<rootNode>
    <innerNode>
    </innerNode>
</rootNode>';


$header  = "POST HTTP/1.0 \r\n";
$header .= "Content-type: text/xml \r\n";
$header .= "Content-length: ".strlen($post_string)." \r\n";
$header .= "Content-transfer-encoding: text \r\n";
$header .= "Connection: close \r\n\r\n"; 
$header .= $post_string;


$request = curl_init('http://localhost/gateway'); // initiate curl object
curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
curl_setopt($request, CURLOPT_POST, true);
curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); 
curl_setopt($request, CURLOPT_CUSTOMREQUEST, $header);

$post_response = curl_exec($request);

echo $post_response;

?>