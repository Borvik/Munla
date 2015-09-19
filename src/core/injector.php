<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * injector
 * Allows applications to register for shutdown events designed to inject code
 * into the resulting html page.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class injector{
    
    //stores all the registered injectors
    private static $injectors = array();
    
    /**
     * Registers a new injector
     * 
     * @param callable $function The function to register as an injector.
     * 
     * @return void
     */
    public static function register(callable $function){
        $target = null; $method = null;
        if( is_string($function) ){
            if( strpos($function, '::') === false )
                $method = $function;
            else{
                $parts = explode('::', $function);
                $target = $parts[0];
                $method = $parts[1];
            }
        }elseif( is_array($function) ){
            $target = $function[0];
            $method = $function[1];
        }
        $has = false; $put = null;
        if( is_null($target) && !is_null($method) ){
            $put = $method;
            $has = in_array($method, self::$injectors);
        }elseif( !is_null($target) && !is_null($method) ){
            if( is_string($target) ){
                $put = sprintf('%s::%s', $target, $method);
                $has = in_array($put, self::$injectors);
            }else{
                $put = $function;
                foreach(self::$injectors as $v){
                    if( is_array($v) && $v[0] == $put[0] && $v[1] == $put[1] ){
                        $has = true;
                        break;
                    }
                }
            }
        }
        if( $has ) log::notice('Method has already been registered as an injector.');
        if( is_null($put) )
            log::error('Error validating callback function registered status.');
        self::$injectors[] = $put;
    }
    
    /**
     * Starts the output bufferring and registers the shutdown function.
     * 
     * @return void
     */
    public static function start(){
        ob_start();
        register_shutdown_function(array(__CLASS__, 'shutdown'));
    }
    
    /**
     * Gets the output buffer and passes the contents to each injector
     * in the order that it was registered, and then outputs the data.
     * 
     * @return void
     */
    public static function shutdown(){
        $data = ob_get_clean();
        foreach(self::$injectors as $injector){
            if( is_string($injector) && strpos($injector, '::') !== false ){
                list($class, $method) = explode('::', $injector, 2);
                $data = $class::$method($data);
            }else
                $data = $injector($data);
        }
        echo $data;
    }
    
}