<?php

class xmlElement{
    
    public $name;
    public $values;
    
    public function __construct($name){
        $args = func_get_args();
        array_shift($args);
        
        if( is_string($name) ) $name = new xmlElementName(null, $name);
        $this->name = $name;
        
        $this->values = array();
        if( count($args) > 0 )
            $this->values = $args;
    }
    
    public function add($param){
        $args = func_get_args();
        if( count($args) > 1 )
            $this->values[] = new xmlElement($args[0], $args[1]);
        elseif( count($args) == 1 )
            $this->values[] = $args[0];
    }
    
}