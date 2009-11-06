<?php

$url = "http://localhost/gateway/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>123456789123456789</api_id>
		<secret_key>dsdf324854s2d1f8s5g43sd21f</secret_key>
	</authentication>
	<request>NewClient</request>
	<params>
		<first_name>David</first_name>
		<last_name>Ryan</last_name>
		<company>ABC Inc.</company>
		<address_1>123 Main Street</address_1>
		<city>Denver</city>
		<state>CO</state>
		<postal_code>80220</postal_code>
		<country>US</country>
		<phone>303319812</phone>
		<email>daveryan187@yahoo.com</email>
	</params>
</request>';

$postfields = "request=".$post_string; 

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 4);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields); 


$data = curl_exec($ch); 

if(curl_errno($ch))
{
    print curl_error($ch);
}
else
{
	curl_close($ch);
    echo $data;
}


?>