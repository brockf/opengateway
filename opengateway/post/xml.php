<?php
/**
 * This file contains the XML class, "xml"
 * Licensed under the BSD license
 * All credits to strangeways (http://www.strangeways.se/)
 * for letting this code out in the free where it belongs!
 *
 * Contributions made by:
 * Fredrik Carlbom <fredrik.carlbom@gmail.com>
 *
 * @author Mikael "Lilleman" Goransson <lilleman@strangeways.se>
 * @version 0.1
 * @package SW-CMS
 */

/**
 * XML class
 *
 * Contributions made by:
 * Fredrik Carlbom <fredrik.carlbom@gmail.com>
 *
 * @author Mikael "Lilleman" Goransson <lilleman@strangeways.se>
 * @version 0.1
 * @package SW-CMS
 * @subpackage xml
 */
class xml {

    /**
     * Content as array
     *
     * @var arr
     */
    var $array = array();

    /**
     * Errors
     *
     * @var arr
     */
    var $errors = array();

    /**
     * The XML
     *
     * @var str
     */
    var $XML = '';

    /**
     * XML Encoding
     *
     * @var str
     */
    var $XMLEncoding = 'ISO-8859-1';

    /**
     * XML default indent char
     *
     * @var str
     */
    var $XMLIndentChar = ' ';

    /**
     * XML number of indents per level
     *
     * @var int
     */
    var $XMLIndentNum = 4;

    /**
     * XML Line Break
     *
     * @var str
     */
    var $XMLLineBreak = "\n";

    /**
     * Sets if the values of each tag should be fixed for forbidden characters (like <> etc)
     *
     * @var bol
     */
    var $XMLSafe = true;

    /**
     * XML Version
     *
     * @var str
     */
    var $XMLVersion = '1.0';

    /**
     * Class constructor
     *
     * @return boolean
     * @access Public
     */
    function xml() {
        return true;
    } // End of xml()

    /**
     * Generate tags recursive
     * (This is the magic XML-creator)
     *
     * @param arr $arr
     * @param int $lvl
     * @param int $prev_lvl - will be one less than lvl if this is the first row, else it'll be the same
     * @return str
     * @access Private
     */
    function generateTag($arr,$lvl = 0,$prev_lvl = 0) {
        $str = '';

        foreach ($arr as $key => $value) {
            if (is_numeric($key)) {
                // Numeric keys are not allowed, so we skip them and treat them as non associated arrays
                if ($prev_lvl != $lvl) {
                    $this->XML .= $this->XMLLineBreak;
                } // End of if ($prev_lvl != $lvl)
                $this->XML .= $this->indent($lvl) . '<' . $this->strToXMLKeySafe($value) . ' />' . $this->XMLLineBreak;
                $prev_lvl = $lvl;
            } else { // End of if ($key == '0' && !isset($arr['1']))
                if ($prev_lvl != $lvl) {
                    $this->XML .= $this->XMLLineBreak;
                } // End of if ($prev_lvl != $lvl)
                $this->XML .= $this->indent($lvl) . '<' . $this->strToXMLKeySafe($key) . '>';

                if (is_array($value))   $this->XML .= $this->generateTag($value,$lvl + 1,$lvl);
                else                    $this->XML .= $this->strToXMLSafe($value);

                // Remove attributes from closing key
                list($key) = explode(' ',$key);

                if (is_array($value)) $this->XML .= $this->indent($lvl);
                $this->XML .= '</' . $this->strToXMLKeySafe($key) . '>' . $this->XMLLineBreak;

                $prev_lvl = $lvl;
            } // End of else to if ($key == '0' && !isset($arr['1']))
        } // End of foreach ($arr as $key => $value)

        return $str;
    } // End of generateTag()

    /**
     * Create an indent string
     *
     * @param int $num - number of indents
     * @return str
     * @access Private
     */
    function indent($num) {
        $str = '';
        for ($i = 1; $i <= ($this->XMLIndentNum * ($num)); $i++) {
            $str .= $this->XMLIndentChar;
        } // End of for ($i = 1; $i <= ($this->XMLIndentNum * ($num)); $i++)

        return $str;
    } // End of indent()

    /**
     * Generate XML from $this->array
     *
     * @param bol $xmlHead - if the XML head should be included
     * @return boolean
     * @access Private
     */
    function generateXML($xmlHead = true) {
        $this->XML = '';

        if ($xmlHead) $this->XML .= $this->getXMLHead();

        $this->XML .= $this->generateTag($this->array);

        return true;
    } // End of generateXML()

    /**
     * Output XML Data
     *
     * @param str $method - Either "return" or "echo" - "return" will make the function return, "echo" will print it to screen with header 'n all
     * @param bol $xmlHead - if the XML head should be included
     * @access Public
     */
    function outputXML($method = 'return',$xmlHead = true) {
        $this->generateXML($xmlHead);

        if ($method == 'return') {
            return $this->XML;
        } elseif ($method == 'echo') { // End of if ($method == 'return')
            header('Content-Type: text/xml');
            echo $this->XML;
            return true;
        } else { // End of elseif ($method == 'echo')
            return false;
        } // End of elseif ($method == 'echo')
    } // End of outputXML()

    /**
     * Set the array of contents
     *
     * @param arr $arr
     * @return boolean
     * @access Public
     */
    function setArray($arr) {
        $this->array = $arr;
        return true;
    } // End of setArray()

    /**
     * Set indent character
     *
     * @param str $str
     * @return boolean
     * @access Public
     */
    function setIndentChar($str) {
        $this->XMLIndentChar = strval($str);
        return true;
    } // End of setIndentChar()

    /**
     * Set number of indents
     *
     * @param int $int
     * @return boolean
     * @access Public
     */
    function setIndentNum($int) {
        $this->XMLIndentNum = intval($int);
        return true;
    } // End of setIndentNum()

    /**
     * Set XML line breaks
     *
     * @param str $str
     * @return boolean
     * @access Public
     */
    function setLineBreak($str) {
        $this->XMLLineBreak = strval($str);
        return true;
    } // End of setLineBreak()

    /**
     * Set the line break to the default for Mac OS
     *
     * @return boolean
     * @access Public
     */
    function setLineBreakForMacOS() {
        return $this->setLineBreak("\r");
    } // End of setLineBreakForMacOS()

    /**
     * Set the line break to the default for UNIX (and Linux)
     *
     * @return boolean
     * @access Public
     */
    function setLineBreakForUNIX() {
        return $this->setLineBreak("\n");
    } // End of setLineBreakForUNIX()

    /**
     * Set the line break to the default for Microsoft Windows
     *
     * @return boolean
     * @access Public
     */
    function setLineBreakForWindows() {
        return $this->setLineBreak("\r\n");
    } // End of setLineBreakForWindows()

    /**
     * Set XML Encoding
     *
     * @param str $str
     * @return boolean
     */
    function setXMLEncoding($str) {
        $this->XMLEncoding = strval($str);
        return true;
    } // End of setXMLEncoding()

    /**
     * Gets the XML Head for use in XML output
     *
     * @return str
     * @access Private
     */
    function getXMLHead() {
        return '<?xml version="' . $this->XMLVersion . '" encoding="' . $this->XMLEncoding . '"?>' . $this->XMLLineBreak;
    } // End of setXMLHead();

    function setXMLSafe($mode) {
        if (is_bool($mode)) {
            $this->XMLSafe = $mode;
            return true;
        } else { // End of if (is_bool($mode))
            return false;
        } // End of else to if (is_bool($mode))
    } // End of setXMLSafe()

    /**
     * Set XML Version (as a string)
     * example: "1.0"
     *
     * @param str $str
     * @return boolean
     * @access Public
     */
    function setXMLVersion($str) {
        $this->XMLVersion = strval($str);
        return true;
    } // End of setXMLVersion()

    /**
     * String to XML Safe for key (tag) values
     *
     * @param str $str
     * @return str
     * @access Private
     */
    function strToXMLKeySafe($str) {
        if (is_numeric($str)) {
            // Due to rules in $this->generateTag(), this should not happend, so just for safety
            $str = 'a' . $str;
        } elseif (is_numeric(substr($str,0,1))) { // End of if (is_numeric($str))
            $str = preg_replace("/^(\\d*)/","",$str);
        } // End of elseif (is_numeric(substr($str,0,1)))

        return $str;
    } // End of strToXMLKeySafe()

    /**
     * String to XML Safe
     *
     * @param str $str
     * @return str
     * @access Private
     */
    function strToXMLSafe($str) {
        if ($this->XMLSafe) {
            $searchArray    = array('<', '>', "'", '"');
            $replaceArray   = array('&lt;', '&gt;', '&apos;', '&quot;');
            $str = str_replace($searchArray,$replaceArray,$str);
        } // End of if ($this->xmlSafe)

        return $str;
    } // End of strToXMLSafe()

} // End of class xml
?> 