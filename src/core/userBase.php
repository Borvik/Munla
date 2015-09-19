<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * userBase
 * Provides base functionality for a user account including permission checking.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class userBase{
    
    /**
     * An array of key/values to help identify the current user.
     */
    protected $user_keys = array();
    
    /**
     * Stores the model of the user data.
     */
    protected $userModel = null;
    
    /**
     * Magic method to make sure only $user_keys is serialized, and not the full user model.
     */
    public function __sleep(){ return array('user_keys'); }
    
    /**
     * Gets the view that should be used for the login form.
     * 
     * @return view
     */
    abstract public function getLoginView();
    
    /**
     * Gets the model of the user data.
     */
    abstract public function getUserModel();
    
    /**
     * Checks if the user has the permission specified.
     * Optional second value provides another value to check against.
     * 
     * This is a user implemented function to support custom
     * permission checking however they may have been implemented.
     * 
     * @param mixed $perm The permission to check against.
     * @param mixed $value (optional) A value to check against.
     * 
     * @return bool
     */
    abstract public function checkPermission($perm, $value = null);
    
    /**
     * Attempts to login a user with the given username and password.
     * 
     * @param string $username The username of the user.
     * @param string $password The user's password.
     * 
     * @return bool
     */
    abstract protected function login($username, $password);
    
    /**
     * Should be run after a login attempt.  Run automatically by munla via the userWrapper.
     * 
     * @param bool $result The result of the login attempt.
     * 
     * @return void
     */
    final public function post_login($result){
        if( $result === true ) $this->regen_id();
    }
    
    /**
     * Should be run after the user is logged out.  Run automatically by munla via the userWrapper.
     * 
     * @return void
     */
    public function post_logout(){ }
    
    protected function get_empty_user(){ return null; }
    
    /**
     * Checks whether a user is logged in or not.
     * 
     * @return bool
     */
    public function is_logged_in(){ return (count($this->user_keys) > 0); }
    
    /**
     * Logs out the current user.
     * 
     * @return void
     */
    public function logout(){ $this->userModel = $this->get_empty_user(); $this->user_keys = array(); }
    
    /**
     * Regenerates the session id.
     * 
     * Wraps the session_regenerate_id call to make sure everything gets passed to the client.
     * 
     * @param bool $deleteOld Determines whether to delete the old session or not. Defaults to TRUE.
     * 
     * @return void
     */
    public function regen_id($deleteOld = true){
        session_regenerate_id($deleteOld);
        $newid = session_id();
        session_write_close();
        
        if( ini_get('session.use_cookies') )
            setcookie(session_name(), $newid, ini_get("session.cookie_lifetime"), "/", ini_get("session.cookie_domain"));
        
        session_id($newid);
        session_start();
    }
    
    /**
     * Checks if the user has the permission specified.
     * Optional second value provides another value to check against.
     * 
     * Wrapper for checkPermission that automatically handles being passed multiple
     * permission to check in the form of an array.
     * 
     * @param mixed $perm The permission to check against.
     * @param mixed $value (optional) A value to check against.
     * 
     * @return bool
     */
    public function hasPermission($perm, $value = null){
        if( is_array($perm) ) return $this->arrPermission($perm);
        return $this->checkPermission($perm, $value);
    }
    
    /**
     * Checks to make sure an array of permissions has been fully satisfied.
     * 
     * @param array $perms 
     *   An array of permissions that may contain qualifiers.
     *   Valid qualifiers are: any, all, or none.  Default is all.
     *   Multiple qualifiers are allowed, but only one takes effect.  In
     *   that case precedence is: all, any, none.
     * 
     * @return bool
     */
    private function arrPermission(array $perms){
        $q = 'all';
        foreach(array('none', 'any', 'all') as $k){
            $idx = array_search($k, $perms);
            if( $idx !== false ){
                $q = $k;
                unset($perms[$idx]);
            }
        }
        foreach($perms as $k => $v){
            if( is_string($k) && !is_bool($v) )
                $r = $this->hasPermission($k, $v);
            elseif( is_string($k) )
                $r = ($this->hasPermission($k) == $v);
            else $r = $this->hasPermission($v);
            
            if( $q == 'any' && $r === true ) return true;
            if( $q == 'none' && $r === true ) return false;
            if( $q == 'all' && $r === false ) return false;
        }
        return ($q == 'any' ? false : true);
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
        if( !isset($this->userModel) ) $this->userModel = $this->getUserModel();
        if( isset($this->userModel) && is_object($this->userModel) ){
            if( get_class($this->userModel) == 'secureClass' ){
                $obj = $this->userModel->getObject();
                if( method_exists($obj, $name) && is_callable(array($obj, $name)) )
                    return call_user_func_array(array($obj, $name), $args);
                log::error(sprintf('The method "%s" does not exist for %s', $name, get_class($obj)));
            }elseif( method_exists($this->userModel, $name) && is_callable(array($this->userModel, $name)) )
                return call_user_func_array(array($this->userModel, $name), $args);
            log::error(sprintf('The method "%s" does not exist for %s', $name, get_class($this->userModel)));
        }
        throw new Exception(sprintf('The method "%s" does not exist for %s.', $name, get_class($this)));
    }
    
    /**
     * Magic method to allow interacting with the user model through the user class.
     * 
     * @param string $name The name of the property to get.
     * 
     * @return mixed
     */
    public function __get($name){
        if( !isset($this->userModel) && $this->is_logged_in() ) $this->userModel = $this->getUserModel();
        if( isset($this->userModel) && is_object($this->userModel) && get_class($this->userModel) == 'secureClass' && is_subclass_of($this->userModel->getObject(), 'model') && isset($this->userModel->{$name}) )
            return $this->userModel->{$name};
        return null;
    }
    
    /**
     * Magic method to allow interacting with the user model through the user class.
     * 
     * @param string $name The name of the property to check.
     * 
     * @return bool
     */
    public function __isset($name){
        if( !isset($this->userModel) && $this->is_logged_in() ) $this->userModel = $this->getUserModel();
        if( isset($this->userModel) && is_object($this->userModel) && get_class($this->userModel) == 'secureClass' && is_subclass_of($this->userModel->getObject(), 'model') )
            return isset($this->userModel->{$name});
        return false;
    }
    
}
