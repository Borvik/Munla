<?php

class xmlElementName{
    
    public $namespace;
    public $name;
    
    public function __construct(xmlNamespace $ns = null, $name = ''){
        $this->namespace = $ns;
        $this->name = $name;
    }
    
    public function hasNamespace(){
        return ($this->namespace !== null && is_object($this->namespace) && get_class($this->namespace) == 'xmlNamespace');
    }
    
}