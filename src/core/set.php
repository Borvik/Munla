<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * set
 * Contains functions that help with "setting" certain kinds of values.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class set extends extender{
    
    /**
     * Checks to make sure the specified array key is set, and is an allowed value.
     * If it isn't set or it is not an allowed value, the value is set to the default.
     * 
     * @param array $array   The array to check the values of.
     * @param mixed $key     The key in the array to check.
     * @param mixed $default The default value to use.
     * @param array $allowed The list of allowed values to check against.
     * 
     * @return void
     */
    public static function array_default(array &$array, $key, $default = null, array $allowed = array()){
        if( is_array($array) && (!array_key_exists($key, $array) || ($array[$key] === null && $default !== null) || (array_key_exists($key, $array) && (count($allowed) > 0 && !in_array($array[$key], $allowed)))) )
            $array[$key] = $default;
    }
    
}