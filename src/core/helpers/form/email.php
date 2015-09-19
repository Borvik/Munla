<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_email
 * Represents an HTML5 email form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_email extends fe_text{
    
    /**
     * Stores the attributes that are allowed for a email element.
     */
    protected static $elAttributes = array('multiple');
    protected static $badAttributes = array('dirname');
    
    /**
     * Creates a new email element.
     * 
     * @param array $attributes The attributes that should be assigned to the email element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'email';
    }
    
    /**
     * Validates a email value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $return = parent::validate($value);
        if( $return !== true ) return $return;
        
        if( isset($value) && strlen(trim($value)) > 0 ){
            $valid = is::email($value, (get::array_def($this->attributes, 'multiple', false) == 'multiple'));
            if( !is_object($valid) || !($valid instanceof emailAddressList || $valid instanceof emailAddress)  ){
                $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
                return sprintf('"%s" has an invalid email address.', $msglbl);
            }
            $value = $valid;
        }
        return true;
    }
}