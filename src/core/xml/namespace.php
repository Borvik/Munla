<?php

class xmlNamespace{
    
    public $namespace;
    
    public function __construct($ns){
        $this->namespace = $ns;
    }
    
    public function __toString(){
        return $this->namespace;
    }
    
    public function __invoke($x){
        return new xmlElementName($this, $x);
    }
    
}