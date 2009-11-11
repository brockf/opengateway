<?php

$url = "http://localhost/gateway/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<request>NewRecurring</request>
	<params>
		<gateway_id>3</gateway_id>
		<customer_id>2</customer_id>
		<order_id>33</order_id>
		<interval>30</interval>
		<interval_unit>days</interval_unit>
		<total_occurences>12</total_occurences>
		<trial_occurences>0</trial_occurences>
		<start_date>2009-12-01</start_date>
		<amount>24.99</amount>
		<trial_amount>0</trial_amount>
		<card_num>4007000000027</card_num>
		<exp_month>10</exp_month>
		<exp_year>2011</exp_year>
		<cvv>123</cvv>
		<description>Monthly Supply</description>
	</params>
</request>';

$postfields = 'request='.$post_string; 

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