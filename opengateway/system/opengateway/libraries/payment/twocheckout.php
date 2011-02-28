<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class twocheckout {

	var $settings;
	
	var $base_url	= 'https://www.2checkout.com/api/';
	var $accept		= 'application';
	var $format		= 'xml';
	
	var $ci;
	
	var $debug = true;
	
	//--------------------------------------------------------------------
	
	function payway() {
		// Init our settings
		$this->settings = $this->Settings();
		
		$this->ci =& get_instance();
	}
	
	//--------------------------------------------------------------------
	
	function Settings()
	{
		$settings = array();
		
		$settings['name'] = '2CheckOut';
		$settings['class_name'] = 'twocheckout';
		$settings['external'] = TRUE;
		$settings['no_credit_card'] = TRUE;
		$settings['description'] = '2CO provides comprehensive e-commerce services to help you preserve cash and focus on growing your business, not managing your payments.';
		$settings['is_preferred'] = 1;
		$settings['setup_fee'] = '$49';
		$settings['monthly_fee'] = 'n/a';
		$settings['transaction_fee'] = '5.5% + $0.45';
		$settings['purchase_link'] = 'http://www.2checkout.com/';
		$settings['allows_updates'] = 0;
		$settings['allows_refunds'] = 1;
		$settings['requires_customer_information'] = 1;
		$settings['requires_customer_ip'] = 0;
		$settings['required_fields'] = array(
										'enabled',
										'mode', 
										'username',
										'password',
										'currency'
										);
										
		$settings['field_details'] = array(
										'enabled' => array(
														'text' => 'Enable this gateway?',
														'type' => 'radio',
														'options' => array(
																		'1' => 'Enabled',
																		'0' => 'Disabled')
														),
										'mode' => array(
														'text' => 'Mode',
														'type' => 'select',
														'options' => array(
																		'live' => 'Live Mode',
																		'test' => 'Test Mode',
																		'dev' => 'Development Server'
																		)
														),
										'username' => array(
														'text' => 'User ID (SID)',
														'type' => 'text'
														),
										
										'password' => array(
														'text' => 'Secret Word (password)',
														'type' => 'text'
														),
										'currency'	=> array(
														'text'	=> 'Currency',
														'type'	=> 'select',
														'options'	=> array(
																		'USD'	=> 'US Dollar',
																		'ARP'	=> 'Argentino Peso',
																		'AUD'	=> 'Australian Dollar',
																		'BRL'	=> 'Brazilian Real',
																		'CAD'	=> 'Canadian Dollar',
																		'DKK'	=> 'Danish Kroner',
																		'EUR'	=> 'Euro',
																		'GBP'	=> 'GBP Sterlings',
																		'HKD'	=> 'Hong Kong Dollar',
																		'INR'	=> 'Indian Rupee',
																		'JPY'	=> 'Japanese Yen',
																		'MXN'	=> 'Mexican Peso',
																		'NAX'	=> 'New Zealand Dollar',
																		'NOK'	=> 'Norwegian Kroner',
																		'ZAL'	=> 'South African Rand',
																		'SEK'	=> 'Swedish Kroner',
																		'CHF'	=> 'Swiss Franc'
																	)
														)
											);
		
		return $settings;
	}
	
	//--------------------------------------------------------------------

	/**
	 *	Verifies that our connection is working as expected by returning the
	 * detailed company info for the user.
	 * 
	 * Also verifies that json is available on the server.
	 */
	public function TestConnection($client_id, $gateway) 
	{
		if (!function_exists('json_encode'))
		{
			return false;
		}
	
		return TRUE;
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Charging with 2CO does NOT require the product to be setup within
	 * the 2CO backend.
	 */
	public function Charge($client_id, $order_id, $gateway, $customer, $amount, $credit_card, $return_url, $cancel_url)
	{
		$this->ci =& get_instance();
		$this->ci->load->helper('url');
	
		$post = array(
			'sid'			=> $gateway['username'],
			'total'			=> $amount,
			'cart_order_id'	=> 'Invoice # '. $order_id,
			'id_type'		=> 1,
		);
		
		// product info
		if (isset($customer['plans'][0]))
		{
			$post['c_prod'] 		= $customer['plans'][0]['id'];
			$post['c_name']			= $customer['plans'][0]['name'];
			$post['c_description']	= $customer['plans'][0]['name'];
			$post['c_price']		= $amount;
		}
		
		// Test/dev mode?
		if ($gateway['mode'] != 'live')
		{
			$post['demo'] = 'Y';
		}
		
		$post['fixed'] = 'Y';
		$post['return_url']	= site_url('callback/twocheckout/confirm/' . $order_id);
		$post['merchant_order_id'] = $order_id;
		$post['pay_method'] = 'CC';
		$post['skip_landing']	= 1;
		
		// Billing info
		if (isset($customer['first_name']))
		{
			$post['card_holder_name']	= $customer['first_name'] .' '. $customer['last_name'];
		}
		if (isset($customer['address_1']) and !empty($customer['address_1']))
		{
			$post['street_address']	 = $customer['address_1'];
			$post['street_address2'] = $customer['address_2'];
			$post['city']	= $customer['city'];
			$post['state']	= $customer['state'];
			$post['zip']	= $customer['postal_code'];
			$post['country']	= $customer['country'];
		}
		$post['email']	= $customer['email'];
		$post['phone']	= $customer['phone'];

		//$response = $this->Process($this->GetAPIUrl($gateway), $post);
		//$response = $this->Process('http://developers.2checkout.com/return_script/', $data);
		
		// Save the Subscription data so we can pull it up later.
		
		// Return redirect information
		$url = site_url('callback/twocheckout/form_redirect/'. $order_id);
		
		$response_array = array(
						'not_completed' => TRUE, // don't mark charge as complete
						'redirect' 		=> $url, // redirect the user to this address
						'charge_id' 	=> $order_id
					);
		$response = $this->ci->response->TransactionResponse(1, $response_array);
		
		if ($this->debug)
		{
			echo '<h2>Charge TransactionResponse</h2>';
			print_r($response);
		}

		return $reponse;
	}
	
	//--------------------------------------------------------------------
	
	public function Recur($client_id, $gateway, $customer, $amount, $charge_today, $start_date, $end_date, $interval, $credit_card, $subscription_id, $total_occurrences, $return_url, $cancel_url) 
	{
		
	}
	
	//--------------------------------------------------------------------
	
	public function CancelRecurring($client_id, $subscription, $gateway) 
	{
		
	}
	
	//--------------------------------------------------------------------
	
	public function UpdateRecurring($client_id, $gateway, $subscription, $customer, $params) 
	{
		
	}
	
	//--------------------------------------------------------------------
	
	public function AutoRecurringCharge ($client_id, $order_id, $gateway, $params) {
		return $this->ChargeRecurring($client_id, $gateway, $params);
	}
	
	//--------------------------------------------------------------------
	
	public function ChargeRecurring($client_id, $gateway, $params) 
	{
		
	}
	
	//--------------------------------------------------------------------
	
	//--------------------------------------------------------------------
	// !CALLBACKS
	//--------------------------------------------------------------------
	
	public function Callback_form_redirect($client_id, $gateway, $charge, $params) 
	{
		echo 'here';
		die();
	}
	
	//--------------------------------------------------------------------
	
	public function Callback_confirm($client_id, $gateway, $charge, $params) 
	{
		
	}
	
	//--------------------------------------------------------------------
	
	
	//--------------------------------------------------------------------
	// !PROCESSORS
	//--------------------------------------------------------------------
	
	public function Process($url_suffix, $data) 
	{
		if(!is_array($data)) 
		{
			$resp = $this->return_response(array('Error' => 'Value passed in was not an array of at least one key/value pair.'));
		} 
		else 
		{
			if (strpos($url_suffix, 'http') !== false)
			{
				$url = $url_suffix;
			}
			else
			{
				$url = $this->base_url . $url_suffix;
			}
			
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: {$this->accept}/{$this->format}"));
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
			//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			//curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	// Verify it belongs to the server.
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);	// Check common exists and matches the server host name
			
			if(count($data) > 0) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			
			$resp = curl_exec($ch);
			print_r(curl_getinfo($ch));
			echo curl_error($ch);
			curl_close($ch);
		}
		
		if ($this->debug)
		{
			echo '<p>URL = '. $url .'</p>';
			echo '<h2>Process Results</h2>';
			var_dump($resp);
			die();
		}

		return $this->return_response($resp);
	}
	
	//--------------------------------------------------------------------
	
	/**
	 *	Formats the return response based upon the content types. 
	 *
	 * @param	string	$contents	An array where keys are nodes and values are the node data
	 * @return	array
	 */
	public function return_response($contents) 
	{
		switch($this->format) {
			case 'xml':
				if(preg_match('/<response>/', $contents)) {
					return $contents;
				} else {
					$xml = new XmlConstruct('response');
					$xml->fromArray($contents);
					return $xml->getDocument();
					return $xml->output();
				}
			break;
			case 'json':
				if(preg_match('/response :/', $contents)) {
					return $contents;
				} else {
					$jsonData = json_encode($contents);
					return json_decode($jsonData);
				}
			break;
			case 'html':
				if(preg_match('/\<dt\>response_code\<\/dt\>/', $contents)) {
					return $contents;
				} else {
					$htmlOut = '';
					foreach($contents as $key => $val) {
						$htmlOut .= "<ul>$key<li>$val</li></ul>\n";
					}
					return $htmlOut;
				}
			break;
		}
	}
	
	//--------------------------------------------------------------------
	
	/**
	 * Returns the proper url for the remote gateway.
	 *
	 * Note that $mode param defaults to false, which will
	 * return the token payments url. If $mode is 'rebill', 
	 * then it will return the rebill url.
	 */
	private function GetAPIUrl ($gateway, $mode = FALSE) {
		if ($mode == FALSE) {
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
		}
		elseif ($mode == 'rebill') {
			// Get the proper URL
			switch($gateway['mode'])
			{
				case 'live':
					$post_url = $gateway['arb_url_live'];
				break;
				case 'test':
					$post_url = $gateway['arb_url_test'];
				break;
				case 'dev':
					$post_url = $gateway['arb_url_dev'];
				break;
			}
		}
		
		return $post_url;
	}
}

// End twocheckout class

//--------------------------------------------------------------------

/**
 * XMLParser Class
 *
 * This class loads an XML document into a SimpleXMLElement that can
 * be processed by the calling application.  This accepts xml strings,
 * files, and DOM objects.  It can also perform the reverse, converting
 * an SimpleXMLElement back into a string, file, or DOM object.
 *
 * I am not sure who the original author of this class is as it was
 * never documented. Henceforth, I am reliquishing ownership of this
 * class.
 */
class XmlConstruct extends XMLWriter
{
		private $formal = false;
    /**
     * Constructor.
     * @param string $prm_rootElementName A root element's name of a current xml document
     * @param string $prm_xsltFilePath Path of a XSLT file.
     * @access public
     * @param null
     */
    public function __construct($prm_rootElementName, $formal=false, $prm_xsltFilePath='') {
				$this->formal = $formal;
        $this->openMemory();
        $this->setIndent(true);
        $this->setIndentString(' ');
				if($this->formal) {
		        $this->startDocument('1.0', 'UTF-8');
				}

        if($prm_xsltFilePath) {
            $this->writePi('xml-stylesheet', 'type="text/xsl" href="'.$prm_xsltFilePath.'"');
        }

        $this->startElement($prm_rootElementName);
    }

    /**
     * Set an element with a text to a current xml document.
     * @access public
     * @param string $prm_elementName An element's name
     * @param string $prm_ElementText An element's text
     * @return null
     */
    public function setElement($prm_elementName, $prm_ElementText) {
        $this->startElement($prm_elementName);
        $this->text($prm_ElementText);
        $this->endElement();
    }

    /**
     * Construct elements and texts from an array.
     * The array should contain an attribute's name in index part
     * and a attribute's text in value part.
     * @access public
     * @param array $prm_array Contains attributes and texts
     * @return null
     */
    public function fromArray($prm_array) {
      if(is_array($prm_array)) {
        foreach ($prm_array as $index => $element) {
          if(is_array($element)) {
            $this->startElement($index);
            $this->fromArray($element);
            $this->endElement();
          }
          else
            $this->setElement($index, $element);
         
        }
      }
    }

    /**
     * Return the content of a current xml document.
     * @access public
     * @param null
     * @return string Xml document
     */
    public function getDocument() {
        $this->endElement();
				if($this->formal) {
		        $this->endDocument();
				}
        return $this->outputMemory();
    }

    /**
     * Output the content of a current xml document.
     * @access public
     * @param null
     */
    public function output() {
        #header('Content-type: text/xml');
        return $this->getDocument();
    }
}
