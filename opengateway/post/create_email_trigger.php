<?php

$url = "http://localhost/index.php/api/";

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<request>
	<authentication>
		<api_id>EB4RTDHWE5F18BDC8ZJ3</api_id>
		<secret_key>FLIDRBM9S8E8PP9DZ9T319HC8WQCTUSINFFKJ7W3</secret_key>
	</authentication>
	<type>NewEmail</type>
	<trigger>recurring_cancel</trigger>
	<plan>0</plan>
	<email_subject>Your card has been charged.</email_subject>
	<email_body>'.htmlentities('<a><strong>Test it yo!</strong></a>').'</email_body>
	<from_name>Electric Function, Inc.</from_name>
	<from_email>daveryan187@yahoo.com</from_email>
	<is_html>1</is_html>
	<client_bcc>0</client_bcc>
</request>';


//echo $post_string;
$postfields = $post_string; 

$ch = curl_init();
//curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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