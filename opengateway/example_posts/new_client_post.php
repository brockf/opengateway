<?php

$url = "http://localhost/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<type>NewClient</type>
	<first_name>Joe</first_name>
	<last_name>Blow</last_name>
	<company>DAR Inc.</company>
	<address_1>123 Main Street</address_1>
	<address_2>Apt. 3</address_2>
	<city>Anytown</city>
	<state>CO</state>
	<postal_code>80220</postal_code>
	<country>US</country>
	<timezone>-7</timezone>
	<phone>3033319812</phone>
	<email>daveryan187@yahoo.com</email>
	<username>dasdfsdsdfgdfsghvzxczveryan18asd7123</username>
	<password>MyPassword187</password>
</request>';

$postfields = $post_string; 

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