<?php

$url = "http://localhost/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<type>UpdateClient</type>
	<client_id>18</client_id>
	<first_name>Michael</first_name>
	<last_name>Smith</last_name>
	<company>Big Company</company>
	<address_1>Schelpenkade 58a</address_1>
	<address_2></address_2>
	<city>Leiden</city>
	<state>Noord Holland</state>
	<postal_code>2311EA</postal_code>
	<country>NL</country>
	<phone>320752570</phone>
	<email>daveryan187@gmail.com</email>
	<username>daveryan1871</username>
	<password>DavesPassword1</password>
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