<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * extender
 * 
 * Allows developers to register new functionality with the Munla core classes.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class extender{
    
    private static $registered = array();
    
    /**
     * Allows the user to override a base method with one of their
     * own, or add a method to the class.
     * 
     * @internal Tried using the "callable" type hint, but that wouldn't
     *           allow nulls without throwing a fatal error.
     * 
     * @param string $name
     *    The name of the new/overridden method.
     * 
     * @param callable|null $func 
     *   The function to call when the method is called or null to remove the override.
     * 
     * @throws InvalidArgumentException if the function is not callable and is not null.
     * 
     * @return void
     */
    public static function override($name, $func){
        $class = get_called_class();
        if( method_exists($class, $name) && !method_exists($class, 'ov_'.$name) && !method_exists($class, 'ov_cache_'.$name) )
            throw new InvalidArgumentException(sprintf('Unable to override function "%s" - it is not marked as overrideable.', $name));
        
        $funcName = '';
        if( $func !== null && !is_callable($func, false, $funcName) )
            throw new InvalidArgumentException(sprintf('Function "%s" is not callable.', $funcName));
        
        
        if( $func === null ){
            //removing the override
            if( !array_key_exists($class, self::$registered) ) return;
            if( !array_key_exists($name, self::$registered[$class]) ) return;
            unset(self::$registered[$class][$name]);
        }else{
            //adding the override
            if( !array_key_exists($class, self::$registered) ) self::$registered[$class] = array();
            self::$registered[$class][$name] = $func;
        }
    }
    
    
    public function __call($name, $args){
        //first remove "cache_" prefix if it is given
        $regName = $name;
        if( !strncmp($regName, 'cache_', 6) ) $regName = substr($regName, 6);
        //first try the registered overrides
        $class = get_called_class();
        if( array_key_exists($class, self::$registered) && array_key_exists($regName, self::$registered[$class]) )
            return call_user_func_array(self::$registered[$class][$regName], $args);
        
        $ovname = 'ov_'.$name;
        if( method_exists($this, $ovname) )
            return call_user_func_array(array($this, $ovname), $args);
        trigger_error(sprintf('Call to undefined method %s::%s()', $class, $name), E_USER_ERROR);
    }
    
    public static function __callStatic($name, $args){
        //first remove "cache_" prefix if it is given
        $regName = $name;
        if( !strncmp($regName, 'cache_', 6) ) $regName = substr($regName, 6);
        //first try the registered overrides
        $class = get_called_class();
        if( array_key_exists($class, self::$registered) && array_key_exists($regName, self::$registered[$class]) )
            return call_user_func_array(self::$registered[$class][$regName], $args);
        
        //no registered override existed, check if it exists as overridable
        $ovname = 'ov_'.$name;
        if( method_exists($class, $ovname) )
            return call_user_func_array(array($class, $ovname), $args);
        trigger_error(sprintf('Call to undefined method %s::%s()', $class, $name), E_USER_ERROR);
    }
    
}