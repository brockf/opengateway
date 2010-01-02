<?php

$url = "http://localhost/index.php/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<type>NewGateway</type>
	<client_id>17</client_id>
	<enabled>1</enabled>
	<mode>live</mode>
	<gateway_type>paypal</gateway_type>
	<user>davery_1260232299_biz_api1.gmail.com</user>
	<pwd>1260232305</pwd>
	<signature>AnbV3mizZPlwOaylLaGkUd3Lc3GiAenv2j4H1BszT0z0m0MrCgqb7Pb8</signature>
	<accept_visa>1</accept_visa>
	<accept_mc>1</accept_mc>
	<accept_discover>1</accept_discover>
	<accept_amex>1</accept_amex>
	<accept_dc>1</accept_dc>
	<enable_arb>1</enable_arb>
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