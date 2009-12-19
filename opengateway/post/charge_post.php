<?php

$url = "http://platform.opengateway.net/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<type>Charge</type>
	<gateway_id>28</gateway_id>
	<customer>
		<first_name>Joe</first_name>
		<last_name>Customer</last_name>
		<address_1>1345 Quebec Street</address_1>
		<address_2>Apt 1</address_2>
		<city>Denver</city>
		<state>CO</state>
		<postal_code>80220</postal_code>
		<country>US</country>
	</customer>
	<credit_card>
		<card_num>4916634239086979</card_num>
		<exp_month>10</exp_month>
		<exp_year>2011</exp_year>
		<cvv>123</cvv>
	</credit_card>
	<customer_ip_address>127.0.0.1</customer_ip_address>
	<amount>10001.00</amount>
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