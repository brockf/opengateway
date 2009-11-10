<?php
class authnet
{
	function Charge($client_id, $gateway, $customer, $params)
	{	
		// Get the proper URL
		switch($gateway['mode'])
		{
			case 'live':
				$post_url = $gateway['url_live'];
			break;
			case 'test':
				$post_url = $gateway['url_test'];
			break;
			case 'dev':
				$post_url = $gateway['url_dev'];
			break;
		}
		
		$post_values = array(
			"x_login"			=> $gateway['login_id'],
			"x_tran_key"		=> $gateway['transaction_key'],
		
			"x_version"			=> "3.1",
			"x_delim_data"		=> "TRUE",
			"x_delim_char"		=> "|",
			"x_relay_response"	=> "FALSE",
		
			"x_type"			=> "AUTH_CAPTURE",
			"x_method"			=> "CC",
			"x_card_num"		=> $params['card_num'],
			"x_exp_date"		=> $params['exp_month'].$params['exp_year'],
		
			"x_amount"			=> $params['amount'],
			"x_description"		=> $params['description'],
		
			"x_first_name"		=> $customer['first_name'],
			"x_last_name"		=> $customer['last_name'],
			"x_address"			=> $customer['address_1'].'-'.$customer['address_2'],
			"x_state"			=> $customer['state'],
			"x_zip"				=> $customer['postal_code']
			);
			
		$post_string = "";
		foreach( $post_values as $key => $value )
			{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
		$post_string = rtrim( $post_string, "& " );
		
		$this->Process($post_url, $post_string);
		
	}
	
	function Process($post_url, $post_string)
	{
		$request = curl_init($post_url); // initiate curl object
		curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); // use HTTP POST to send form data
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment this line if you get no gateway response.
			$post_response = curl_exec($request); // execute curl post and store results in $post_response
		
		curl_close ($request); // close curl object
		
		$response_array = explode('|',$post_response);
		
		echo "<OL>\n";
		foreach ($response_array as $value)
		{
			echo "<LI>" . $value . "&nbsp;</LI>\n";
		}
		echo "</OL>\n";
	}
}