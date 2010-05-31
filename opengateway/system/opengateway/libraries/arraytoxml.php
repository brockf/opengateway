<?php

class ArrayToXML
{
    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
     *
     * @param array $data
     * @param string $rootNodeName - what you want the root node to be - defaultsto data.
     * @param SimpleXMLElement $xml - should only be used recursively
     * @return string XML
     */
    public static function toXML( $data, $rootNodeName = 'ResultSet', &$xml=null ) {

        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if ( ini_get('zend.ze1_compatibility_mode') == 1 ) ini_set ( 'zend.ze1_compatibility_mode', 0 );
        if ( is_null( $xml ) ) $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
        

        // loop through the data passed in.
        foreach( $data as $key => $value ) {
			
        	$numeric = FALSE;
            // no numeric keys in our xml please!
            if ( is_numeric( $key ) ) {
                $numeric = 1;
                $key = $rootNodeName;
            }

            // delete any char not allowed in XML element names
            $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

            // if there is another array found recrusively call this function
            if ( is_array( $value ) ) {
                $node = ArrayToXML::isAssoc( $value ) || $numeric ? $xml->addChild( $key ) : $xml;

                // recrusive call.
                if ( $numeric ) $key = 'anon';
                ArrayToXML::toXml( $value, $key, $node );
            } else {
                // add single node.
                $value = (is_numeric($value)) ? $value : ArrayToXML::xmlEntities(ArrayToXML::utf8tohtml($value));
                $xml->addChild( $key, $value );
            }
        }

    	// if you want the XML to be formatted, use the below instead to return the XML
        $doc = new DOMDocument('1.0');
        $doc->preserveWhiteSpace = FALSE;
        $doc->loadXML( $xml->asXML() );
        $doc->formatOutput = TRUE;
        return $doc->saveXML();
    }

    /**
     * Convert an XML document to a multi dimensional array
     * Pass in an XML document (or SimpleXMLElement object) and this recrusively loops through and builds a representative array
     *
     * @param string $xml - XML document - can optionally be a SimpleXMLElement object
     * @return array ARRAY
     */
    public static function toArray( $xml ) {
        if ( is_string( $xml ) ) $xml = new SimpleXMLElement( $xml );
        $children = $xml->children();
        if ( !$children ) return (string) $xml;
        $arr = array();
        foreach ( $children as $key => $node ) {
            $node = ArrayToXML::toArray( $node );

            // support for 'anon' non-associative arrays
            if ( $key == 'anon' ) $key = count( $arr );

            // if the node is already set, put it into an array
            if ( isset( $arr[$key] ) ) {
                if ( !is_array( $arr[$key] ) || $arr[$key][0] == null ) $arr[$key] = array( $arr[$key] );
                $arr[$key][] = $node;
            } else {
                $arr[$key] = $node;
            }
        }
        return $arr;
    }

    // determine if a variable is an associative array
    public static function isAssoc( $array ) {
        return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
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