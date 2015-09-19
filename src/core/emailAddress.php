<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * emailAddress
 * Represents an email address.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class emailAddress implements Serializable, JsonSerializable{
    
    public $email = null;
    public $user = null;
    public $domain = null;
    
    public function __construct($email = null, $user = null, $domain = null){
        $this->email = $email;
        $this->user = $user;
        $this->domain = $domain;
    }
    
    /**
     * Returns the email address.
     */
    public function __toString(){
        return $this->email;
    }
    
    public function serialize(){
        return serialize(array($this->email, $this->user, $this->domain));
    }
    public function unserialize($data){
        list($this->email, $this->user, $this->domain) = unserialize($data);
    }
    public function jsonSerialize(){ return $this->__toString(); }
}