<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_radio
 * Represents an HTML5 text radio element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_radio extends fe_input implements fe_validator{
    
    /**
     * Stores the attributes that are allowed for a radio button element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'type', 'checked', 'value', 'autofocus', 'required');
    
    /**
     * Stores the custom attributes that are allowed for a select element.
     */
    protected static $customAttributes = array('allowchange');
    
    /**
     * Special array to store names of already validated radio buttons.
     */
    private static $validated = array();
    
    /**
     * Creates a new radio form element.
     * 
     * @param array $attributes The attributes that should be assigned to the radio element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'radio';
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
     * Validates a radio button value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $args = func_get_args(); array_shift($args); $fields = $args[0];
        $name = $this->getName();
        if( in_array($name, fe_radio::$validated) ) return true;
        
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        $required = false; $allowchange = false;
        $values = array();
        foreach($fields as $field){
            if( !($field instanceof fe_radio) || $field->getName() != $name ) continue;
            if( get::array_def($field->attributes, 'required', false) == 'required' )
                $required = true;
            if( get::array_def($this->attributes, 'allowchange', false, array(true, false)) )
                $allowchange = true;
            $values[] = get::array_def($field->attributes, 'value');
        }
        
        fe_radio::$validated[] = $name;
        
        if( $required && !isset($value) )
            return sprintf('"%s" is a required field.', $msglbl);
        
        if( !$allowchange && isset($value) && !in_array($value, $values) )
            return sprintf('"%s" is not a valid value for "%s" - please choose from the list.', $value, $msglbl);
        
        return true;
    }
}