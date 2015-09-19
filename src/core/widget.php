<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * widget
 * Provides base functionality for a widget - uses a decorator pattern.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class widget{
    
    protected $values = null;
    protected $nocache = false;
    
    abstract public function generate();
    
    final public function setValues(array $values = null){
        $this->values = $values;
        return $this;
    }
    
    final public function show(){
        if( func_num_args() > 0 ){
            $args = func_get_args();
            if( count($args) == 1 && is_array($args[0]) )
                $this->setValues($args[0]);
            else
                $this->setValues($args);
        }
        
        if( $this->nocache ){
            echo $this->generate();
            return;
        }
        $fname = get_class($this).'->show';
        $sname = base64_encode(gzcompress(serialize(array(get_class($this), spl_object_hash($this), 'show', $this->values))));
        $value = cache::checkFunc($fname, $sname, $result);
        if( $result === true ){
            if( strlen($value['output']) > 0 )
                echo $value['output'];
            return $value['result'];
        }
        $r = null;
        ob_start();
        echo $this->generate();
        $output = ob_get_contents();
        ob_end_flush();
        return cache::func($fname, $sname, $r, $output);
    }
    
}