<?php

$url = "http://localhost/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>U1ZOH92275K2JUMV2JXC</api_id>
		<secret_key>RKNTFSWQI7KRM3KA72FX3L7L6U1CBN6LSYKROANX</secret_key>
	</authentication>
	<type>Charge</type>
	<gateway_id>10</gateway_id>
	<customer_id>1000</customer_id>
	<credit_card>
		<card_num>4916634239087777</card_num>
		<exp_month>10</exp_month>
		<exp_year>2017</exp_year>
		<cvv>123</cvv>
	</credit_card>
	<customer_ip_address>127.0.0.1</customer_ip_address>
	<amount>100.01</amount>
	<description>Goods and Services</description>
</request>';

$postfields = $post_string; 
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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