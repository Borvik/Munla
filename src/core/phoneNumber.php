<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * phoneNumber
 * Represents an phone number.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class phoneNumber implements Serializable, JsonSerializable{
    
    public $original = null;
    public $formatted = null;
    public $xformatted = null;
    public $areacode = null;
    public $exchange = null;
    public $number = null;
    
    /**
     * Returns the formatted value for this telephone number.
     */
    public function __toString(){
        return isset($this->xformatted) ? $this->xformatted : $this->formatted;
    }
    
    public function serialize(){
        return serialize(array(
            $this->original,
            $this->formatted,
            $this->xformatted,
            $this->areacode,
            $this->exchange,
            $this->number
            ));
    }
    public function unserialize($data){
        list($this->original,
            $this->formatted,
            $this->xformatted,
            $this->areacode,
            $this->exchange,
            $this->number) = unserialize($data);
    }
    public function jsonSerialize(){ return $this->__toString(); }
}