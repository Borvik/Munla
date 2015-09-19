<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * htmlHelper
 * Contains functions that help with html generation.
 * 
 * @package    Munla
 * @subpackage core\helpers
 * @author     Chris Kolkman
 * @version    1.0
 */
class htmlHelper extends extender{
    
    /**
     * Stores the actual path to the folder that contains the html elements definitions.
     */
    public static $heFolder = null;
    
    /**
     * Allows the user to turn automatic echoing off.
     */
    private $echoOff = false;
    
    /**
     * htmlHelper constructor.
     * 
     * @param bool $echoOff (optional) FALSE, form tags will automatically echo. TRUE form tags will be returned.
     * 
     * @return void
     */
    public function __construct($echoOff = false){
        $this->echoOff = is_bool($echoOff) ? $echoOff : isset($echoOff);
    }
    
    /**
     * Autoload includes class files as they are needed.
     * 
     * @param string $name The name of the class to load.
     */
    public static function autoload($name){
        if( substr($name, 0, 3) == 'he_' && isset(htmlHelper::$heFolder) )
            require (htmlHelper::$heFolder.substr($name, 3).'.php');
    }
    
    /**
     * Allows the user to turn on/off automatic echoing of form tags.
     * 
     * @param bool $value FALSE, form tags will automatically echo. TRUE form tags will be returned.
     * 
     * @return void
     */
    public function setEchoOff($value){
        $this->echoOff = is_bool($value) ? $value : isset($value);
    }
    
    /**
     * Checks whether the given array has keys that are in the second array.
     * 
     * @param array $arr The array to check the keys of.
     * @param array $html The list of keys to check for.
     * 
     * @return bool
     */
    private function hasHtmlAttributes(array &$arr, array &$html){
        return (count(array_intersect(array_keys($arr), $html)) > 0);
    }
    
    /**
     * Generates an anchor element.
     * 
     * @param mixed $name  The content of the anchor element.
     * @param string $webpath      The path following the domain name.
     * @param string|bool $https   Whether the url should be secure or not. Valid values: true, false, 'http', 'https'.
     * @param int $port            The port number that should be used (ex. http://www.google.com:57/).
     * @param string|array $get    The query string that should be appended to the url.
     * @param string $argSeparator The separator that should splite arguements in the query string.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return he_a
     */
    public function a($content, $webpath){
        $args = func_get_args(); array_shift($args); array_shift($args);
        $https = null; $port = null; $get = null; $argSeparator = '&'; $attributes = array();
        $allowed = he_a::acceptedAttributes();
        if( count($args) == 1 && is_array($args[0]) ){
            $vargs = array('https', 'port', 'get', 'argSeparator', 'attributes');
            $is_assoc = false; //must use this method rather than is::assoc_array because GET can be an assoc array
            foreach($vargs as $v){
                if( is::existset($args[0], $v) ){
                    $is_assoc = true;
                    break;
                }
            }
            if( $is_assoc ){
                //arguements were passed as associative array
                if( is::existset($args[0], 'https') ) $https = ($args[0]['https'] == 'https' || $args[0]['https'] === true);
                if( is::existset($args[0], 'port') ) $port = $args[0]['port'];
                if( is::existset($args[0], 'get') ) $get = $args[0]['get'];
                if( is::existset($args[0], 'argSeparator') ) $argSeparator = $args[0]['argSep'];
                if( is::existset($args[0], 'attributes') && is_array($args[0]['attributes']) ) $attributes = $args[0]['attributes'];
            }else{
                if( $this->hasHtmlAttributes($args[0], $allowed) ) $attributes = $args[0];
                else $get = $args[0]; //not an associative array, the array is meant for get
            }
        }else{
            // cycle through the arguments and assign them based on type
            // remember to not go out of order
            //  https,      port,     get,      argSeparator, attributes
            // bool|string,  int, string|array, string,       array
            $argPos = 0;
            while(count($args) > 0){
                $arg = array_shift($args);
                if( is_string($arg) ){
                    $argl = strtolower($arg);
                    if( $argPos < 1 && ($argl == 'https' || $argl == 'http') ){ $argPos = 1; $https = ($argl == 'https'); continue; }
                    if( $argPos > 0 && $argPos <= 2 ){
                        if( strlen($arg) > 1 ) $get = $arg;
                        $argPos = 3;
                        continue;
                    }
                    if( $argPos > 2 ){ $argSeparator = $arg; break; }
                }
                if( $argPos < 1 && is_bool($arg) ){  $https = $arg; $argPos = 1; }
                if( $argPos <= 1 && is_int($arg) ){ $port = $arg; $argPos = 2; }
                if( is_array($arg) ){
                    if( $argPos <= 2 ){
                        //must check for attributes
                        if( $this->hasHtmlAttributes($arg, $allowed) ){
                            foreach($arg as $n => $v){
                                $n = strtolower($n);
                                if( !array_key_exists($n, $attributes) ){
                                    if( array_key_exists($n, htmlElement::$enumAttributes) && !in_array($v, htmlElement::$enumAttributes[$n]) && array_key_exists($v, htmlElement::$enumAttributes[$n]) )
                                        $v = htmlElement::$enumAttributes[$n][$v];
                                    elseif( in_array($n, htmlElement::$boolAttributes) ){
                                        if( (is_bool($v) && !$v) || (!is_bool($v) && $v !== 'true') ) continue;
                                        $v = $n;
                                    }
                                    $attributes[$n] = $v;
                                }
                            }
                        }else
                            $get = $arg;
                    }
                    if( $argPos > 2 ){
                        //must be attributes
                        foreach($arg as $n => $v){
                            $n = strtolower($n);
                            if( !array_key_exists($n, $attributes) ){
                                if( array_key_exists($n, htmlElement::$enumAttributes) && !in_array($v, htmlElement::$enumAttributes[$n]) && array_key_exists($v, htmlElement::$enumAttributes[$n]) )
                                    $v = htmlElement::$enumAttributes[$n][$v];
                                elseif( in_array($n, htmlElement::$boolAttributes) ){
                                    if( (is_bool($v) && !$v) || (!is_bool($v) && $v !== 'true') ) continue;
                                    $v = $n;
                                }
                                $attributes[$n] = $v;
                            }
                        }
                        $argPos = 4;
                    }
                }
            }
        }
        
        $attributes['href'] = get::url($webpath, $https, $port, $get, $argSeparator);//, $attributes);
        $e = new he_a($content, $attributes);
        if( !$this->echoOff ) echo $e;
        return $e;
    }
    
    /**
     * Generates an image element.
     * 
     * @param string $src The path to the image.
     * @param string $alt 
     * @param int $width
     * @param int $height
     * @param string $class
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     */
    public function img($src){
        if( !is_string($src) ) throw new Exception('First parameter expected to be a path string.');
        $args = func_get_args(); array_shift($args);
        $attributes = array();
        if( count($args) > 0 ){
            $allowed = he_img::acceptedAttributes();
            // alt,  width, height, class, attributes
            //string, int,   int,  string,   array
            $argPos = 0;
            while(count($args) > 0){
                $arg = array_shift($args);
                if( is_string($arg) ){
                    if( $argPos < 1 ){ $attributes['alt'] = $arg; $argPos = 1; continue; }
                    if( $argPos > 1 ){ $attributes['class'] = $arg; $argPos = 4; continue; }
                }
                if( is_int($arg) ){
                    if( $argPos < 2 ){ $attributes['width'] = $arg; $argPos = 2; continue; }
                    if( $argPos == 2 ){ $attributes['height'] = $arg; $argPos = 3; continue; }
                }
                if( is_array($arg) ){
                    if( $this->hasHtmlAttributes($arg, $allowed) ){
                        foreach($arg as $n => $v){
                            $n = strtolower($n);
                            if( !array_key_exists($n, $attributes) ){
                                if( array_key_exists($n, htmlElement::$enumAttributes) && !in_array($v, htmlElement::$enumAttributes[$n]) && array_key_exists($v, htmlElement::$enumAttributes[$n]) )
                                    $v = htmlElement::$enumAttributes[$n][$v];
                                elseif( in_array($n, htmlElement::$boolAttributes) ){
                                    if( (is_bool($v) && !$v) || (!is_bool($v) && $v !== 'true') ) continue;
                                    $v = $n;
                                }
                                $attributes[$n] = $v;
                            }
                        }
                    }
                }
            }
        }
        
        //get url
        $attributes['src'] = get::file_url($src);
        $e = new he_img($attributes);
        if( !$this->echoOff ) echo $e;
        return $e;
    }
}

/* Get the directory of THIS file so we can more easily specify sub directories */
$thisdir = strtr(dirname(__FILE__), '\\', '/');
if( substr($thisdir, -1) != '/' ) $thisdir .= '/';

htmlHelper::$heFolder = $thisdir.'html/';

require htmlHelper::$heFolder.'htmlElement.php';
spl_autoload_register(array('htmlHelper', 'autoload'), true, true);