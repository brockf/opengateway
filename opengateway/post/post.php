<?php

$url = "http://localhost/gateway/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<request>NewGateway</request>
	<params>
		<client_id>17</client_id>
		<gateway_type>psigate</gateway_type>
		<enabled>1</enabled>
		<store_id>1234564789</store_id>
		<passphrase>23ggsdfy4sragb4h564w5hba464</passphrase>
		<mode>live</mode>
		<accept_visa>1</accept_visa>
		<accept_mc>1</accept_mc>
		<accept_amex>1</accept_amex>
		<accept_discover>1</accept_discover>
		<accept_dc>1</accept_dc>
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