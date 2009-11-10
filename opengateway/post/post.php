<?php

$url = "http://localhost/gateway/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<request>Charge</request>
	<params>
		<gateway_id>3</gateway_id>
		<amount>19.99</amount>
		<card_num>4007000000027</card_num>
		<exp_month>10</exp_month>
		<exp_year>2011</exp_year>
		<cvv>123</cvv>
		<first_name>David</first_name>
		<last_name>Ryan</last_name>
		<address_1>123 Main Street</address_1>
		<address_2>APT 1</address_2>
		<city>Denver</city>
		<state>CO</state>
		<postal_code>80220</postal_code>
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