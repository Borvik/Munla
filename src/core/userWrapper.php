<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * userWrapper
 * Provides a wrapper for the user object to allow pre/post methods on standard user functions.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
final class userWrapper{
    
    protected $internal; //stores the object to wrap
    
    /**
     * userWrapper constructor
     * 
     * @param userBase $value The class to provide user wrapping for.  Must inherit from userBase.
     * 
     * @return void
     */
    public function __construct(userBase $value){
        $this->internal = $value;
    }
    
    /**
     * Magic method to allow interacting with the user model through the user class.
     * 
     * @param string $name The method name to call.
     * @param array $args The parameters for the method to call.
     * 
     * @return mixed
     */
    public function __call($name, $args){
        if( method_exists($this->internal, $name) && is_callable(array($this->internal, $name)) ){
            $result = call_user_func_array(array($this->internal, $name), $args);
            if( $name == 'login' ) $this->internal->post_login($result);
            elseif( $name == 'logout' ) $this->internal->post_logout();
            return $result;
        }
        //not on user class itself - check the contained user model.
        $model = $this->internal->getUserModel();
        if( isset($model) && is_object($model) ){
            if( get_class($model) == 'secureClass' ){
                $obj = $model->getObject();
                if( method_exists($obj, $name) && is_callable(array($obj, $name)) )
                    return call_user_func_array(array($obj, $name), $args);
                var_dump(debug_backtrace());
                log::error(sprintf('The method "%s" does not exist for %s', $name, get_class($obj)));
            }elseif( method_exists($model, $name) && is_callable(array($model, $name)) )
                return call_user_func_array(array($model, $name), $args);
            log::error(sprintf('The method "%s" does not exist for %s', $name, get_class($model)));
        }
        log::error(sprintf('The method "%s" does not exist for %s', $name, get_class($this->internal)));
    }
    
    /**
     * Magic method to allow setting properties of the secured object.
     * 
     * @param string $name The name of the property to set.
     * @param mixed $value The value to set the property to.
     * 
     * @return void
     */
    public function __set($name, $value){
        $this->internal->{$name} = $value;
    }
    
    /**
     * Magic method to allow retrieving properties of the secured object.
     * 
     * @param string $name The name of the property to get.
     * 
     * @return mixed
     */
    public function __get($name){
        return $this->internal->{$name};
    }
    
    /**
     * Magic method to allow isset() to work on properties of the secured object.
     * 
     * @param string $name The name of the property to check.
     * 
     * @return bool
     */
    public function __isset($name){
        return isset($this->internal->{$name});
    }
    
    /**
     * Magic method to allow unset() to work on properties of the secured object.
     * 
     * @param string $name The name of the property to unset.
     * 
     * @return void
     */
    public function __unset($name){
        unset($this->internal->{$name});
    }
    
    /**
     * Magic method to allow the secured object to be converted into a string.
     * 
     * @return string
     */
    public function __toString(){
        if( method_exists($this->internal, '__toString') && is_callable(array($this->internal, '__toString')) )
            return $this->internal->__toString();
        log::error(sprintf('Object of class %s could not be converted to a string', get_class($this->internal)));
    }
}