<?php

/**
* Coupon Check
* JSON Response
*/

$api_id = '';
$secret_key = '';
$api_url = 'http://www.example.com/api/';

if (isset($_POST['coupon'])) {
	$coupon = $_POST['coupon'];
}
elseif (isset($_GET['coupon'])) {
	$coupon = $_GET['coupon'];
}
else {
	$coupon = FALSE;
}

if (isset($_POST['plan_id'])) {
	$plan_id = $_POST['plan_id'];
}
elseif (isset($_GET['plan_id'])) {
	$plan_id = $_GET['plan_id'];
}
else {
	$plan_id = FALSE;
}

if (isset($_POST['amount'])) {
	$amount = $_POST['amount'];
}
elseif (isset($_GET['amount'])) {
	$amount = $_GET['amount'];
}
else {
	$amount = FALSE;
}

$xml = '<?xml version="1.0" encoding="UTF-8"?>
		 <request>
	     <authentication>
	     	<api_id>' . $api_id . '</api_id>
	     	<secret_key>' . $secret_key . '</secret_key>
	     </authentication>
	     <type>CouponValidate</type>';
	     
if ($coupon != FALSE) {
	$xml .= '<coupon>' . $coupon . '</coupon>';
}

if ($plan_id != FALSE) {
	$xml .= '<plan_id>' . $plan_id . '</plan_id>';
}

if ($amount != FALSE) {
	$xml .= '<amount>' . $amount . '</amount>';
}

$xml .= '</request>';
	    	
$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml); 

$data = curl_exec($ch);

$response = convert_to_array($data);

echo convert_to_json($response);

function convert_to_json ($arr) {
    if(function_exists('json_encode')) return json_encode($arr); //Lastest versions of PHP already has this functionality.
    $parts = array();
    $is_list = false;

    //Find out if the given array is a numerical array
    $keys = array_keys($arr);
    $max_length = count($arr)-1;
    if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1
        $is_list = true;
        for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position
            if($i != $keys[$i]) { //A key fails at position check.
                $is_list = false; //It is an associative array.
                break;
            }
        }
    }

    foreach($arr as $key=>$value) {
        if(is_array($value)) { //Custom handling for arrays
            if($is_list) $parts[] = array2json($value); /* :RECURSION: */
            else $parts[] = '"' . $key . '":' . array2json($value); /* :RECURSION: */
        } else {
            $str = '';
            if(!$is_list) $str = '"' . $key . '":';

            //Custom handling for multiple data types
            if(is_numeric($value)) $str .= $value; //Numbers
            elseif($value === false) $str .= 'false'; //The booleans
            elseif($value === true) $str .= 'true';
            else $str .= '"' . addslashes($value) . '"'; //All other things
            // :TODO: Is there any more datatype we should be in the lookout for? (Object?)

            $parts[] = $str;
        }
    }
    $json = implode(',',$parts);
    
    if($is_list) return '[' . $json . ']';//Return numerical JSON
    return '{' . $json . '}';//Return associative JSON
} 

function convert_to_array ($xml) {
    if (is_string($xml)) $xml = new SimpleXMLElement($xml);
    $children = $xml->children();
    if ( !$children ) return (string) $xml;
    $arr = array();
    foreach ($children as $key => $node) {
        $node = convert_to_array($node);

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