<?php

class xmlCData{
    
    public $cdata;
    
    public function __construct($cdata){
        $this->cdata = $cdata;
    }
    
    public function __toString(){
        return $this->cdata;
    }
}