<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * emailAddressList
 * Represents an email address list.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class emailAddressList implements ArrayAccess, Countable, IteratorAggregate, Serializable, JsonSerializable{
    
    private $list = array();
    
    public function count(){ return count($this->list); }
    public function offsetExists($offset){ return isset($this->list[$offset]); }
    public function offsetGet($offset){ return isset($this->list[$offset]) ? $this->list[$offset] : null; }
    public function offsetSet($offset, $value){ $this->list[$offset] = $value; }
    public function offsetUnset($offset){ unset($this->list[$offset]); }
    public function getIterator(){ return new ArrayIterator($this->list); }
    
    public function toString($sep = ','){
        return implode($sep, $this->list);
    }
    
    /**
     * Returns the list email addresses as a string.
     */
    public function __toString(){
        return $this->toString();
    }
    
    public function serialize(){ return serialize($this->list); }
    public function unserialize($data){ $this->list = unserialize($data); }
    public function jsonSerialize(){ return $this->__toString(); }
}