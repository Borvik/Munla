<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_checkbox
 * Represents an HTML5 text checkbox element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_checkbox extends fe_input implements fe_validator{
    
    /**
     * Store the attributes that are allowed for a checkbox element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'type', 'checked', 'value', 'autofocus', 'required');
    
    /**
     * Creates a new checkbox form element.
     * 
     * @param array $attributes The attributes that should be assigned to the checkbox element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'checkbox';
    }
    
    /**
     * Sets the checked state for this element.
     * 
     * @param bool $checked
     * 
     * @return void
     */
    public function setCheckedState($checked){
        if( $checked ) $this->attributes['checked'] = 'checked';
        else unset($this->attributes['checked']);
    }
    
    /**
     * Validates a checkbox value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $required = (get::array_def($this->attributes, 'required', false) == 'required');
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        
        if( $required && !isset($value) )
            return sprintf('"%s" is a required field.', $msglbl);
        
        return true;
    }
}