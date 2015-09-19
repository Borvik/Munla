<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_session
 * Represents a special hidden form value that doesn't even get output to the browser.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_session extends fe_input{
    
    protected static $elAttributes = array('name', 'value');
    
    /**
     * Creates a new session form element.
     * 
     * @param array $attributes The attributes that should be assigned to the session element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'session';
    }
    
    /**
     * Gets the value of this session element.
     * 
     * @return mixed
     */
    public function get_value(){
        if( array_key_exists('value', $this->attributes) ) return $this->attributes['value'];
        return null;
    }
    
    /**
     * Generates the HTML of the form element - which in this case is always an empty string.
     * 
     * @return string
     */
    public function __toString(){ return ''; }
    
}