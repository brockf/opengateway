<?php

$url = "http://localhost/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<type>Recur</type>
	<gateway_id>63</gateway_id>
	<amount>24.99</amount>
	<customer>
		<first_name>Ahab</first_name>
		<last_name>Arab</last_name>
		<address_1>123 Main Street</address_1>
		<address_2>Apt. 1</address_2>
		<city>Denver</city>
		<state>CO</state>
		<postal_code>80220</postal_code>
		<country>US</country>
	</customer>
	<credit_card>
		<name>Moses Malone</name>
		<card_num>4024007155715823</card_num>
		<exp_month>04</exp_month>
		<exp_year>2010</exp_year>
		<cvv>123</cvv>
	</credit_card>
	<recur>
		<start_date>2010-01-20</start_date>
		<plan_id>16</plan_id>
	</recur>
</request>';

$postfields = $post_string; 

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields); 


$data = curl_exec($ch); 

echo $data;