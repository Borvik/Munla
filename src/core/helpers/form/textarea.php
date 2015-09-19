<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_textarea
 * Represents an HTML5 textarea form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_textarea extends formElement implements fe_validator{
    
    /**
     * Stores the attributes allowed for a textarea element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'readonly', 'maxlength', 'autofocus', 'required', 'placeholder', 'dirname', 'rows', 'wrap', 'cols');
    
    /**
     * Stores the custom attributes allowed for a textarea element.
     */
    protected static $customAttributes = array('value');
    
    /**
     * Creates a new textarea form element.
     * 
     * @param array $attributes The attributes that should be assigned to the textarea element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
    }
    
    /**
     * Generates the HTML for the form element.
     * 
     * @return string
     */
    public function __toString(){
        $this->attributes['id'] = $this->getId();
        $this->attributes['name'] = $this->getName();
        $value = get::array_def($this->attributes, 'value', '');
        $html = sprintf('<textarea%s>%s</textarea>', get::formattedAttributes($this->getAttributes()), get::entities($value));
        if( is::existset($this->attributes, 'autofocus') )
            $html .= $this->getAutoFocusScript($this->attributes['id']);
        return $html;
    }
    
    /**
     * Validates a text value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $required = (get::array_def($this->attributes, 'required', false) == 'required');
        $maxlength = get::array_def($this->attributes, 'maxlength', 0);
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        
        if( $required && (!isset($value) || strlen(trim($value)) < 1) )
            return sprintf('"%s" is a required field.', $msglbl);
        
        if( $maxlength > 0 && strlen(trim($value)) > $maxlength )
            return sprintf('"%s" has to many characters - the max length is %s.', $msglbl, $maxlength);
        
        return true;
    }
}