<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * secureClass
 * Decorator class that controls permissive access to class methods.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
final class secureClass{
    
    protected $internal; //stores the object to wrap
    
    /**
     * secureClass constructor
     * 
     * @param secureProvider $value The class to provide security wrapping for.  Must inherit from secureProvider.
     * 
     * @return void
     */
    public function __construct(secureProvider $value){
        $this->internal = $value;
    }
    
    private function before1($name){
        if( is_array($this->access) && array_key_exists($name, $this->access) ){
            if( $this->access[$name] === null ) return true;
            
            return munla::$user->hasPermission($this->access[$name]);
        }
        return true;
    }
    private function before2($name){
        if( is_array($this->access) && array_key_exists($name, $this->access) ){
            if( $this->access[$name] === null ) return true;
            
            if( isset(munla::$user) && is_object(munla::$user) && is_subclass_of(munla::$user, 'userBase') ){
                $perm = munla::$user->hasPermission($this->access[$name]);
                if( !$perm ){
                    $class = get_class($this);
                    if( strlen($class) > 10 && strtolower(substr($class, -10)) == 'controller' ) $class = substr($class, 0, -10);
                    $this->error_msg = sprintf('Permission denied.  Invalid permissions to access %s/%s.', $class, $name);
                }
                return $perm;
            }
            return false;
        }
        return true;
    }
    
    /**
     * Returns the object that is being secured.
     * 
     * @return mixed
     */
    public function getObject(){ return $this->internal; }
    
    /**
     * Checks the access permission for the given method name.  Checks SSL context, and user permission.
     * 
     * @param string $name The method the check.
     * 
     * @throws SSLException|PermissionException when access permission is invalid.
     * 
     * @return void
     */
    public function check_permission($name){
        //first ignore some functions for some classes
        if( is_subclass_of($this->internal, 'controller') && $name == 'getAction' ) return;
        //check SSL access
        $ssl = $this->internal->getSSL();
        if( isset($ssl) ){
            if( (is_bool($ssl) && $ssl != is::ssl()) || (is_array($ssl) && array_key_exists($name, $ssl) && is_bool($ssl[$name]) && $ssl[$name] != is::ssl()) )
                throw new SSLException(sprintf('Invalid context to run "%s" on %s.  Must be %sssl.', $name, get_class($this->internal), ($ssl ? '' : 'non-')));
        }
        
        //check permission access
        $access = $this->internal->getAccess();
        if( is_array($access) && array_key_exists($name, $access) && isset($access[$name]) ){
            $ret = munla::$user->hasPermission($access[$name]);
            if( is_string($ret) )
                throw new PermissionException($ret);
            elseif( $ret === false )
                throw new PermissionException(sprintf('Invalid permissions to run "%s" on %s.', $name, get_class($this->internal)));
        }
    }
    
    /**
     * Magic method to enable checking for access permission BEFORE calling the real method
     * on the object provided to be secured.
     * 
     * @param string $name The method to call.
     * @param array $args The parameters to pass to the method.
     * 
     * @return mixed
     */
    public function __call($name, $args){
        if( method_exists($this->internal, $name) && is_callable(array($this->internal, $name)) ){
            $permName = $name;
            $suffixes = $this->internal->getLinkedSuffixes();
            if( is_array($suffixes) ){
                $nl = strlen($name);
                foreach($suffixes as $suffix){
                    $sl = strlen($suffix);
                    if( $nl > $sl && substr($name, -$sl) === $suffix ){
                        $permName = substr($name, 0, -$sl);
                        break;
                    }
                }
            }
            $this->check_permission($permName);
            return call_user_func_array(array($this->internal, $name), $args);
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

class SSLException extends Exception{ }
class PermissionException extends Exception{ }
