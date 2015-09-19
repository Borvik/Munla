<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_number
 * Represents an HTML5 number form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_number extends fe_textboxnumeric implements fe_validator{
    
    /**
     * Stores the attributes that are allowed for a number element.
     */
    protected static $elAttributes = array('placeholder');
    
    /**
     * Stores the custom attributes that are allowed for a select element.
     */
    protected static $customAttributes = array('integeronly');
    
    /**
     * Creates a new number form element.
     * 
     * @param array $attributes The attributes that should be assigned to the number element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'number';
    }
    
    /**
     * Validates a number value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $required = (get::array_def($this->attributes, 'required', false) == 'required');
        $integeronly = get::array_def($this->attributes, 'integeronly', false, array(true, false));
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        
        if( $required && (!isset($value) || strlen(trim($value)) < 1) )
            return sprintf('"%s" is a required field.', $msglbl);
        
        if( isset($value) && strlen(trim($value)) > 0 ){
            $value = trim($value);
            $valid = $integeronly ? (preg_match('/^[+-]?\d+$/', $value) ? (int)$value : false) : is::float($value);
            $errorText = $integeronly ? 'integer' : 'number';
            $min = get::array_def($this->attributes, 'min', null);
            $max = get::array_def($this->attributes, 'max', null);
            
            if( $valid === false )
                return sprintf('"%s" is not a valid %s.', $msglbl, $errorText);
            elseif( isset($min) && $valid < $min )
                return sprintf('"%s" is not allowed to be less than %s.', $msglbl, $min);
            elseif( isset($max) && $valid > $max )
                return sprintf('"%s" is not allowed to be greater than %s.', $msglbl, $max);
            $value = $valid;
        }
        return true;
    }
}