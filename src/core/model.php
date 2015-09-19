<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * model
 * Provides base functionality for a model - uses a decorator pattern.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class model extends modelCache implements JsonSerializable{
    
    protected $values = array(); //stores the values for the model
    private $orig_values = null;
    
    /**
     * Creates a new model with the provided values.
     * 
     * @param string $class (special) The class name of the model to create. This is required 
     *                      when calling from this base class, otherwise skipped.
     * @param array|object $values The values that the model should be initialized with.
     * 
     * @throws InvalidArgumentException
     * 
     * @return secureClass Returns a secureClass instance of the model.
     */
    final public static function createNew($values){
        $args = func_get_args();
        $class = get_called_class();
        $mvalues = $values;
        
        if( $class == 'model' && (count($args) != 2 || !is_string($args[0]) || (!is_array($args[1]) && !is_object($args[1]))) )
            throw new InvalidArgumentException('Invalid argument(s) for model::createNew. Expects a string, and an array.');
        elseif( $class != 'model' && (count($args) != 1 || (!is_array($values) && !is_object($values))) )
            throw new InvalidArgumentException(sprintf('Invalid argument for %s::createNew.  Expects an array.', $class));
        
        if( count($args) == 2 ){
            $class = $args[0];
            //check if the class ends with "Model" if not append it.
            if( (strlen($class) >= 5 && substr($class, -5) != 'Model') || strlen($class) < 5 )
                $class .= 'Model';
            if( !class_exists($class) )
                throw new InvalidArgumentException(sprintf('Class for model %s was not found.', $args[0]));
            $mvalues = $args[1];
        }
        
        $obj = new $class();
        if( !is_array($mvalues) || count($mvalues) > 0 )
            $obj->values = $obj->prepare_data($mvalues);
        $obj->orig_values = $obj->values;
        
        return new secureClass($obj);
    }
    
    public function jsonSerialize(){ return $this->values; }
    
    /**
     * Prepares the data before setting it.
     * 
     * Doesn't do much more than return the values as is - overriden though, can modify the data.
     * 
     * @param array|object $values The data to prepare.
     * 
     * @return array|object
     */
    protected function prepare_data($values){ return $values; }
    
    /**
     * Gets the raw values.
     * 
     * @return array|object
     */
    final public function getData(){ return $this->values; }
    
    final public function has_changed($field = null){
        if( !isset($this->orig_values) ){
            if( !is_array($this->values) || count($this->values) > 0 )
                return true;
            return false;
        }
        
        $oo = is_object($this->orig_values);
        $vo = is_object($this->values);
        
        $keys = $oo ? array_keys(get_object_vars($this->orig_values)) : array_keys($this->orig_values);
        $nkeys = $vo ? array_keys(get_object_vars($this->values)) : array_keys($this->values);
        
        if( isset($field) ){
            if( $field === false ){
                $this->orig_values = $this->values;
                return false;
            }
            
            $ofieldexists = in_array($field, $keys);
            $nfieldexists = in_array($field, $nkeys);
            if( $ofieldexists != $nfieldexists ) return true;
            if( !$ofieldexists ) return false;
            
            $ov = $oo ? $this->orig_values->{$field} : $this->orig_values[$field];
            $nv = $vo ? $this->values->{$field} : $this->values[$field];
            return ($ov != $nv);
        }
        
        if( count($keys) != count($nkeys) ) return true;
        $keys_found = true; $has_diff = false;
        foreach($keys as $k){
            if( !in_array($k, $nkeys) ){
                $keys_found = false;
                break;
            }else{
                $ov = $oo ? $this->orig_values->{$k} : $this->orig_values[$k];
                $nv = $vo ? $this->values->{$k} : $this->values[$k];
                if( $ov != $nv ){
                    $has_diff = true;
                    break;
                }
            }
        }
        return (!$keys_found || $has_diff);
    }
    
    /**
     * Magic method to allow easy setting of model properties.
     * 
     * @param string $name The name of the property to set.
     * @param mixed $value The value to set the property to.
     * 
     * @return void
     */
    public function __set($name, $value){
        if( is_array($this->values) )
            $this->values[$name] = $value;
        elseif( is_object($this->values) )
            $this->values->{$name} = $value;
    }
    
    /**
     * Magic method to allow easy getting of model properties.
     * 
     * @param string $name The name of the property to get.
     * 
     * @return mixed
     */
    public function __get($name){
        if( is_array($this->values) && array_key_exists($name, $this->values) )
            return $this->values[$name];
        elseif( is_object($this->values) && isset($this->values->{$name}) )
            return $this->values->{$name};
        return null;
    }
    
    /**
     * Magic method to allow isset() to work on model properties.
     *
     * @param string $name The name of the property to check.
     * 
     * @return bool
     */
    public function __isset($name){
        if( is_array($this->values) && array_key_exists($name, $this->values) )
            return isset($this->values[$name]);
        elseif( is_object($this->values) )
            return isset($this->values->{$name});
        return false;
    }
    
    /**
     * Magic method to allow unset() to work on model properties.
     * 
     * @param string $name The name of the property to unset.
     * 
     * @return void
     */
    public function __unset($name){
        if( is_array($this->values) && array_key_exists($name, $this->values) )
            unset($this->values[$name]);
        elseif( is_object($this->values) )
            unset($this->values->{$name});
    }
}