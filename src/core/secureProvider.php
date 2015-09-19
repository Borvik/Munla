<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * secureProvider
 * Class to allow another to work with the secure class.  Provides necessary variables and methods to make it work.
 * 
 * @package    Framework
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class secureProvider{
    
    /**
     * Stores the access level requirements.
     * The method name should be the key and the access level requirement is the value.
     * 
     * What the access level value should be is determined by the application using it.
     */
    protected $access = array();
    
    /**
     * Stores the suffixes for indentical permission checking.
     */
    protected $linkedMethodSuffixes = array();
    
    /**
     * Stores the SSL access permission.
     * 
     * null - every method is open
     * true - every method requires ssl
     * false - every method requires ssl not be set
     * array - method name, with true or false (like above) as the value
     */
    protected $ssl = null;
    
    /**
     * Retrieves the ssl access permissions.  Used by secureClass.
     * 
     * @return mixed
     */
    final public function getSSL(){ return $this->ssl; }
    
    /**
     * Retrieves the access level requirements.  Used by secureClass.
     * 
     * @return array
     */
    final public function getAccess(){ return $this->access; }
    
    /**
     * Retrieves the linked suffixes.  Used by secureClass.
     * 
     * @return array
     */
    final public function getLinkedSuffixes(){ return $this->linkedMethodSuffixes; }
    
}