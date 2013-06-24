<?php

$url = "http://localhost/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>U1ZOH92275K2JUMV2JXC</api_id>
		<secret_key>RKNTFSWQI7KRM3KA72FX3L7L6U1CBN6LSYKROANX</secret_key>
	</authentication>
	<type>Refund</type>
		<gateway_id>3</gateway_id>
		<customer_id>1000</customer_id>
		<charge_id>1276</charge_id>
		<amount>19.99</amount>
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