<?php

/**
* OpenGateway Class
*
* A basic class for handling request parameters and authenications for an OpenGateway server
*
* @package OpenGateway
* @author Electric Function, Inc.
* @version 1.0
* @copyright 2010, Electric Function, Inc.
*/

class OpenGateway
{
	public $params;
	public $post_url;
	public $api_id;
	public $secret_key;

	public function __construct () {
		// initiate parameter storage
		$this->params = new stdClass();
	}

	/**
	* Authenticate
	*
	* Set the API ID, Secret Key, and Server
	*
	* @param string $api_id API Identifier
	* @param string $secret_key Secret Key
	* @param string $server Secure URL for the OpenGateway server (optional)
	* @return bool TRUE
	*/
	public function Authenticate ($api_id, $secret_key, $server = 'https://platform.opengateway.net/api')  {
		$this->api_id = $api_id;
		$this->secret_key = $secret_key;
		$this->post_url = $server;

		return true;
	}

	/**
	* Set Request Type
	*
	* Sets the request type
	*
	* @param string $method The request type for the request
	* @return bool TRUE
	*/
	public function SetMethod($method)  {
		$this->method = ucwords($method);

		return TRUE;
	}

	/**
	* Set a Parameter
	*
	* Sets a parameter for the request
	*
	* @param string $name Name of the parameter
	* @param string $value The Value
	* @param string $parent The parent node name (optional)
	* @return bool TRUE;
	*/
    public function Param($name, $value, $parent = FALSE)  {

    	if (!isset($this->params))
    	{
    		$this->params = new stdClass();
    	}

        if (!empty($parent)) {
        	// initiate parent parameter storage
        	if (!isset($this->params->$parent)) {
	        	$this->params->$parent = new stdClass();
        	}

       	    $this->params->$parent->$name = $this->xmlEntities($this->utf8tohtml($value));
        } else {
        	$this->params->$name = $this->xmlEntities($this->utf8tohtml($value));
        }

        return true;
    }

    /**
    * Use a Coupon
    *
    * @param string $coupon_string
    */
    public function Coupon($coupon_code) {
    	$this->params->coupon = $coupon_code;
    }

	/**
	* Process the Request
	*
	* Sends the request to the server in XML and returns a PHP array of the response
	*
	* @param bool $debug Set to TRUE to return the XML being sent without sending it
	* @return array The response from the server
	*/
	public function Process($debug = FALSE)  {
		if ($this->post_url == '') {
			return FALSE;
		}

	    // See which params are set
	    $i=0;
	    if(isset($this->params)) {
		    foreach($this->params as $key => $value) {
		      	if(is_object($value)) {
		      		$xml_params[$i] = '<'.$key.'>';
		      		foreach($value as $key1 => $value1) {
		      			$xml_params[$i] .= '<'.strtolower($key1).'>'.$value1.'</'.strtolower($key1).'>';
		      		}
		      		$xml_params[$i] .= '</'.$key.'>';
		      	} else {
		      		$xml_params[$i] = '<'.strtolower($key).'>'.$value.'</'.strtolower($key).'>';
		      	}
		      	$i++;
		    }
	    }

	    // put our XML together
	    $xml = '<?xml version="1.0" encoding="UTF-8"?><request>';
	    if(isset($this->api_id) AND isset($this->secret_key)) {
	    	$xml .= '<authentication><api_id>' . $this->api_id . '</api_id><secret_key>' . $this->secret_key . '</secret_key></authentication>';
	    }

	    if(isset($this->method)) {
	    	$xml .= '<type>'.$this->method.'</type>';
	    }

	    if(isset($xml_params)) {
	    	foreach($xml_params as $xml_param)
	    	{
	    		$xml .= $xml_param;
	    	}
	    }

	    $xml .= '</request>';

	    if ($debug) {
	    	$xml = simplexml_load_string($xml);
	    	$doc = new DOMDocument('1.0');
        	$doc->preserveWhiteSpace = false;
        	$doc->loadXML($xml->asXML());
        	$doc->formatOutput = true;
        	echo $doc->saveXML();

        	return true;
	    }

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_URL, $this->post_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

		$data = curl_exec($ch);
	//die($data);
		if (curl_errno($ch)) {
		    print curl_error($ch);
		}
		else {
			curl_close($ch);

			// empty parameters
			$this->params = new stdClass;

			// check for a system error
			if (strpos($data, '<div') === 0) {
				// this isn't XML, it's an error
				$error = strip_tags($data);

				echo 'A system error was incurred in the process of the ' . $this->method . ' call: ' . $error;
				return FALSE;
			}

			$xml = $this->toArray($data);

			// automatically redirect if we received a <redirect> node
			if (isset($xml['redirect']) and !empty($xml['redirect'])) {
				header('Location: ' . $xml['redirect']);
				die();
			}

		    return $xml;
		}
	}

	/**
	* Convert XML to PHP Array
	*
	* @param string $xml XML string
	* @return array PHP array
	*/
	/**
	* Convert XML to PHP Array
	*
	* @param string $xml XML string
	* @return array PHP array
	*/
	private function toArray($xml) {
        if (is_string($xml)) $xml = new SimpleXMLElement($xml);
        $children = $xml->children();
        if ( !$children ) return (string) $xml;
        $arr = array();
        foreach ($children as $key => $node) {
            $node = $this->toArray($node);

            // support for 'anon' non-associative arrays
            if ($key == 'anon') {
            	$key = count($arr);
            }

            // if the node is already set, put it into an array
            if (isset($arr[$key])) {
                if (!is_array($arr[$key]) || !isset($arr[$key][0]) || $arr[$key][0] == null) {
                	$arr[$key] = array($arr[$key]);
                }
                $arr[$key][] = $node;
            } else {
                $arr[$key] = $node;
            }
        }

        return $arr;
    }

    public static function utf8tohtml($utf8, $encodeTags = TRUE) {
	    $result = '';
	    for ($i = 0; $i < strlen($utf8); $i++) {
	        $char = $utf8[$i];
	        $ascii = ord($char);
	        if ($ascii < 128) {
	            // one-byte character
	            $result .= ($encodeTags) ? htmlentities($char) : $char;
	        } else if ($ascii < 192) {
	            // non-utf8 character or not a start byte
	        } else if ($ascii < 224) {
	            // two-byte character
	            $result .= htmlentities(substr($utf8, $i, 2), ENT_QUOTES, 'UTF-8');
	            $i++;
	        } else if ($ascii < 240) {
	            // three-byte character
	            $ascii1 = ord($utf8[$i+1]);
	            $ascii2 = ord($utf8[$i+2]);
	            $unicode = (15 & $ascii) * 4096 +
	                       (63 & $ascii1) * 64 +
	                       (63 & $ascii2);
	            $result .= "&#$unicode;";
	            $i += 2;
	        } else if ($ascii < 248) {
	            // four-byte character
	            $ascii1 = ord($utf8[$i+1]);
	            $ascii2 = ord($utf8[$i+2]);
	            $ascii3 = ord($utf8[$i+3]);
	            $unicode = (15 & $ascii) * 262144 +
	                       (63 & $ascii1) * 4096 +
	                       (63 & $ascii2) * 64 +
	                       (63 & $ascii3);
	            $result .= "&#$unicode;";
	            $i += 3;
	        }
	    }

	    return $result;
	}

	public static function xmlEntities($str)
	{
		$xml = array('&#34;','&#38;','&#38;','&#60;','&#62;','&#160;','&#161;','&#162;','&#163;','&#164;','&#165;','&#166;','&#167;','&#168;','&#169;','&#170;','&#171;','&#172;','&#173;','&#174;','&#175;','&#176;','&#177;','&#178;','&#179;','&#180;','&#181;','&#182;','&#183;','&#184;','&#185;','&#186;','&#187;','&#188;','&#189;','&#190;','&#191;','&#192;','&#193;','&#194;','&#195;','&#196;','&#197;','&#198;','&#199;','&#200;','&#201;','&#202;','&#203;','&#204;','&#205;','&#206;','&#207;','&#208;','&#209;','&#210;','&#211;','&#212;','&#213;','&#214;','&#215;','&#216;','&#217;','&#218;','&#219;','&#220;','&#221;','&#222;','&#223;','&#224;','&#225;','&#226;','&#227;','&#228;','&#229;','&#230;','&#231;','&#232;','&#233;','&#234;','&#235;','&#236;','&#237;','&#238;','&#239;','&#240;','&#241;','&#242;','&#243;','&#244;','&#245;','&#246;','&#247;','&#248;','&#249;','&#250;','&#251;','&#252;','&#253;','&#254;','&#255;');
	    $html = array('&quot;','&amp;','&amp;','&lt;','&gt;','&nbsp;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&sup1;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
	    $str = str_replace($html,$xml,$str);
	    $str = str_ireplace($html,$xml,$str);

	    return $str;
	}
}

/**
* Charges Class Extension
*
* Handles single charge requests
* @package OpenGateway
* @author Electric Function, Inc.
* @version 1.0
* @copyright 2010, Electric Function, Inc.
*/
class Charge extends OpenGateway
{
	/**
	* Set Amount
	*
	* Sets the amount of the charge
	*
	* @param string $amount The amount to charge, e.g. "10.99"
	* @return bool TRUE upon success
	*/
	public function Amount($amount)  {
		$this->Param('amount', $amount);

		return true;
	}

	/**
	* Set Credit Card
	*
	* Sets the credit card information
	*
	* @param string $name Credit card holder's name
	* @param int $number Credit card number
	* @param int $exp_month 2-digit representation of expiry month
	* @param int $exp_year 2-digit representation of expiry year
	* @param int $security_code Security code (optional)
	* @return bool TRUE upon success
	*/
	public function CreditCard($name, $number, $exp_month, $exp_year, $security_code = FALSE)  {
		$number = str_replace(' ','',$number);
		$number = trim($number);

		$this->Param('name', $name, 'credit_card');
		$this->Param('card_num', $number, 'credit_card');
		$this->Param('exp_month', $exp_month, 'credit_card');
		$this->Param('exp_year', $exp_year, 'credit_card');
		if($security_code) {
			$this->Param('cvv', $security_code, 'credit_card');
		}

		return true;
	}

	/**
	* Set customer data
	*
	* @param string $first_name Customer's first name
	* @param string $last_name Customer's last name
	* @param string $company Company name
	* @param string $address_1 Address first line
	* @param string $address_2 Second address line (optional)
	* @param string $city Customer's city
	* @param string $state Customer's state, 2-character representation for USA and Canada addresses
	* @param string $country 2-character ISO-standard country code
	* @param string $postal_code Customer's postal or zip code
	* @param string $phone Phone number in any format
	* @param string $email Customer's email
	* @return bool TRUE upon success
	*/
	public function Customer($first_name, $last_name, $company, $address_1, $address_2, $city, $state, $country, $postal_code, $phone, $email)  {
		$this->Param('first_name', $first_name, 'customer');
		$this->Param('last_name', $last_name, 'customer');
		$this->Param('company', $company, 'customer');
		$this->Param('address_1', $address_1, 'customer');
		$this->Param('address_2', $address_2, 'customer');
		$this->Param('city', $city, 'customer');
		$this->Param('state', $state, 'customer');
		$this->Param('country', $country, 'customer');
		$this->Param('postal_code', $postal_code, 'customer');
		$this->Param('phone', $phone, 'customer');
		$this->Param('email', $email, 'customer');

		return true;
	}

	/**
	* Use a Customer ID
	*
	* Link the charge to an existing customer by ID, doesn't require Customer information
	*
	* @param int $customer_id The ID of the existing customer
	* @return bool TRUE upon success
	*/
	public function UseCustomer ($customer_id) {
		$this->Param('customer_id',$customer_id);

		return true;
	}

	/**
	* Set Gateway ID
	*
	* If you have multiple gateways, specify which gateway to use.
	*
	* @param int $gateway_id The ID of the gateway
	* @return bool TRUE upon success
	*/
	public function UseGateway($gateway_id)  {
		$this->Param('gateway_id', $gateway_id);

		return true;
	}

	/**
	* Process Charge
	*
	* @param bool $debug Set to TRUE to return the request XML and not send the request.
	* @return array Response array
	*/
	public function Charge($debug = FALSE)  {
		// add IP address
		$this->Param('customer_ip_address',$_SERVER["REMOTE_ADDR"]);

		$this->SetMethod('Charge');
		return $this->Process($debug);
	}
}

/**
* Recur Class Extension
*
* Handles recurring charge requests
* @package OpenGateway
* @author Electric Function, Inc.
* @version 1.0
* @copyright 2010, Electric Function, Inc.
*/
class Recur extends OpenGateway
{
	/**
	* Set Amount
	*
	* Sets the amount of the charge
	*
	* @param string $amount The amount to charge, e.g. "10.99"
	* @return bool TRUE upon success
	*/
	public function Amount($amount)  {
		$this->Param('amount', $amount);

		return true;
	}

	/**
	* Set Credit Card
	*
	* Sets the credit card information
	*
	* @param string $name Credit card holder's name
	* @param int $number Credit card number
	* @param int $exp_month 2-digit representation of expiry month
	* @param int $exp_year 2-digit representation of expiry year
	* @param int $security_code Security code (optional)
	* @return bool TRUE upon success
	*/
	public function CreditCard($name, $number, $exp_month, $exp_year, $security_code = FALSE)  {
		$number = str_replace(' ','',$number);
		$number = trim($number);

		$this->Param('name', $name, 'credit_card');
		$this->Param('card_num', $number, 'credit_card');
		$this->Param('exp_month', $exp_month, 'credit_card');
		$this->Param('exp_year', $exp_year, 'credit_card');
		if($security_code) {
			$this->Param('cvv', $security_code, 'credit_card');
		}

		return true;
	}

	/**
	* Set customer data
	*
	* @param string $first_name Customer's first name
	* @param string $last_name Customer's last name
	* @param string $company Company name
	* @param string $address_1 Address first line
	* @param string $address_2 Second address line (optional)
	* @param string $city Customer's city
	* @param string $state Customer's state, 2-character representation for USA and Canada addresses
	* @param string $country 2-character ISO-standard country code
	* @param string $postal_code Customer's postal or zip code
	* @param string $phone Phone number in any format
	* @param string $email Customer's email
	* @return bool TRUE upon success
	*/
	public function Customer($first_name, $last_name, $company, $address_1, $address_2, $city, $state, $country, $postal_code, $phone, $email)  {
		$this->Param('first_name', $first_name, 'customer');
		$this->Param('last_name', $last_name, 'customer');
		$this->Param('company', $company, 'customer');
		$this->Param('address_1', $address_1, 'customer');
		$this->Param('address_2', $address_2, 'customer');
		$this->Param('city', $city, 'customer');
		$this->Param('state', $state, 'customer');
		$this->Param('country', $country, 'customer');
		$this->Param('postal_code', $postal_code, 'customer');
		$this->Param('phone', $phone, 'customer');
		$this->Param('email', $email, 'customer');

		return true;
	}

	/**
	* Use a Customer ID
	*
	* Link the charge to an existing customer by ID, doesn't require Customer information
	*
	* @param int $customer_id The ID of the existing customer
	* @return bool TRUE upon success
	*/
	public function UseCustomer ($customer_id) {
		$this->Param('customer_id',$customer_id);

		return true;
	}

	/**
	* Use a Plan ID
	*
	* Link the charge to a plan, doesn't require a Schedule
	*
	* @param int $plan_id The ID of the plan
	* @return bool TRUE upon success
	*/
	public function UsePlan($plan_id)  {
		$this->Param('plan_id', $plan_id, 'recur');

		return true;
	}

	/**
	* Set Gateway ID
	*
	* If you have multiple gateways, specify which gateway to use.
	*
	* @param int $gateway_id The ID of the gateway
	* @return bool TRUE upon success
	*/
	public function UseGateway($gateway_id)  {
		$this->Param('gateway_id', $gateway_id);

		return true;
	}

	/**
	* Set Recurring Schedule
	*
	* Sets the payment schedule
	*
	* @param int $interval Number of days between charges
	* @param int $free_trial Number of days to wait before the first charge.  Added to start_date if that is set. (optional)
	* @param int $occurrences Number of occurrences.  Overwritten by end_date if that is set. (optional)
	* @param string $start_date Day to make first charge.  Set to FALSE for today.  Use any standard format. (optional)
	* @param string $end_date Day at which to not make any charges afterwards.  Use any standard format. (optional)
	* @return bool TRUE upon success
	*/
	public function Schedule($interval, $free_trial = 0, $occurrences = FALSE, $start_date = FALSE, $end_date = FALSE)  {
		$this->Param('interval', $interval, 'recur');
		if ($free_trial != 0) {
			$this->Param('free_trial', $free_trial, 'recur');
		}
		if ($occurrences) {
			$this->Param('occurrences', $occurrences, 'recur');
		}
		if ($start_date) {
			$this->Param('start_date', $start_date, 'recur');
		}
		if($end_date) {
			$this->Param('end_date', $end_date, 'recur');
		}

		return true;
	}

	/**
	* Process Charge
	*
	* @param bool $debug Set to TRUE to return the request XML and not send the request.
	* @return array Response array
	*/
	public function Charge($debug = FALSE)  {
		$this->Param('customer_ip_address',$_SERVER["REMOTE_ADDR"]);

		$this->SetMethod('Recur');
		return $this->Process($debug);
	}
}