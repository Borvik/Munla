<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * url
 * Represents a url.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class url implements ArrayAccess, Countable, IteratorAggregate, Serializable, JsonSerializable{
    
    private $list = array();
    
    public function __construct(array $parsedUrl = null){
        $this->list = $parsedUrl;
    }
    
    public function count(){ return count($this->list); }
    public function offsetExists($offset){ return isset($this->list[$offset]); }
    public function offsetGet($offset){ return isset($this->list[$offset]) ? $this->list[$offset] : null; }
    public function offsetSet($offset, $value){ $this->list[$offset] = $value; }
    public function offsetUnset($offset){ unset($this->list[$offset]); }
    public function getIterator(){ return new ArrayIterator($this->list); }
    
    /**
     * Returns the full url.
     */
    public function __toString(){
        if( isset($this->list['fullmatch']) ) return $this->list['fullmatch'];
        $url  = isset($this->list['scheme']) ? $this->list['scheme'].'://' : '';
        $user = isset($this->list['user']) ? $this->list['user'] : '';
        $pass = isset($this->list['pass']) ? ':'.$this->list['pass'] : '';
        $pass = ($user || $pass) ? $pass.'@' : '';
        $url .= $user.$pass;
        $url .= isset($this->list['host']) ? $this->list['host'] : '';
        $url .= isset($this->list['port']) ? ':'.$this->list['port'] : '';
        $url .= isset($this->list['path']) ? $this->list['path'] : '';
        $url .= isset($this->list['query']) ? '?'.$this->list['query'] : '';
        $url .= isset($this->list['fragment']) ? '#'.$this->list['fragment'] : '';
        return $url;
    }
    
    public function serialize(){ return serialize($this->list); }
    public function unserialize($data){ $this->list = unserialize($data); }
    public function jsonSerialize(){ return $this->list; }
}