<?php

class xmlDocument{
    
    public $root;
    public $encoding;
    
    private $doc;
    
    public function __construct(xmlElement $root){
        $this->root = $root;
        $this->encoding = 'utf-8';
    }
    
    public function save(){
        $this->doc = new DOMDocument();
        $this->doc->encoding = $this->encoding;
        $this->doc->formatOutput = true;
        $this->writeElement($this->doc, $this->root);
        $xml = $this->doc->saveXML();
        $this->doc = null;
        return $xml;
    }
    public function __toString(){
        return $this->save();
    }
    
    private function writeElement(&$appendTo, $element){
        $args = func_get_args();
        array_shift($args);
        if( count($args) == 1 && $element !== null && is_object($element) && get_class($element) == 'xmlElement' ){
            $this->_writeElement($element->name, $element->values, $appendTo);
        }elseif( count($args) == 2 ){
            if( is_string($args[0]) && is_array($args[1]) )
                $this->_writeElement(new xmlElementName(null, $args[0]), $args[1], $appendTo);
            elseif( is_object($args[0]) && get_class($args[0]) == 'xmlElementName' && is_array($args[1]) )
                $this->_writeElement($args[0], $args[1], $appendTo);
        }
    }
    
    private function _writeElement(xmlElementName $name, array $values, &$appendTo){
        if( count($values) == 1 && !(is_object($values[0]) && get_class($values[0]) == 'xmlElement') ){
            $e = $this->doc->createElement($name->name);
            if( $name->hasNamespace() )
                $e->setAttribute('xmlns', (string)$name->namespace);
            if( is_object($values[0]) && get_class($values[0]) == 'xmlCData' )
                $e->appendChild($this->doc->createCDATASection((string)$values[0]));
            else
                $e->appendChild($this->doc->createTextNode(($values[0] === null ? '' : $values[0])));
            $appendTo->appendChild($e);
        }elseif( count($values) > 0 ){
            $e = $this->doc->createElement($name->name);
            if( $name->hasNamespace() )
                $e->setAttribute('xmlns', (string)$name->namespace);
            foreach($values as $value){
                if( is_object($value) && get_class($value) == 'xmlElement' )
                    $this->writeElement($e, $value);
            }
            $appendTo->appendChild($e);
        }
    }
}
