<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * singleUseArray
 * Provides an array that can persist it's values only once (unless the values were changed).
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class singleUseArray implements ArrayAccess, Countable, IteratorAggregate, Serializable{
    
    private $changedValues = array();
    private $values = array();
    
    public function serialize(){
        $ok = array();
        foreach($this->values as $k => $v)
            if( array_key_exists($k, $this->changedValues) && $this->changedValues[$k] === true )
                $ok[$k] = $v;
        return serialize($ok);
    }
    public function unserialize($data){
        $this->values = unserialize($data);
        $this->changedValues = array();
    }
    
    public function count(){ return count($this->values); }
    public function getIterator(){ return new ArrayIterator($this->values); }
    
    public function persist($name){ $this->changedValues[$name] = true; }
    public function desist($name){ $this->changedValues[$name] = false; }
    
    public function keyExists($key){ return array_key_exists($key, $this->values); }
    public function offsetExists($offset){ return isset($this->values[$offset]); }
    public function offsetGet($offset){
        if( array_key_exists($offset, $this->values) ) return $this->values[$offset];
        return null;
    }
    public function offsetSet($offset, $value){
        $k = null;
        if( is_null($offset) ){
            $this->values[] = $value;
            $k = end(array_keys($this->values));
        }else{
            $k = $offset;
            $this->values[$offset] = $value;
        }
        $this->changedValues[$k] = true;
    }
    public function offsetUnset($offset){
        unset($this->values[$offset]);
        unset($this->changedValues[$offset]);
    }
}