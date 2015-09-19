<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_textbox
 * Represents an HTML5 textbox - used as the basis for other textbox based fields.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class fe_textbox extends fe_input implements fe_validator{
    
    /**
     * Stores the attributes that are allowed for a text element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'type', 'autocomplete', 'autofocus', 'list', 'value', 'readonly', 'required', 'placeholder');
    
    /**
     * Stores an optional datalist to be assigned to this text field.
     */
    protected $datalist = null;
    
    /**
     * Creates a new text form element.
     * 
     * @param array $attributes The attributes that should be assigned to the text element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'text';
    }
    
    /**
     * Assigns a datalist to this text element.
     * 
     * @param fe_datalist $list The datalist to assign to this element.
     * 
     * @return void
     */
    public function setDatalist(fe_datalist $list){
        $this->datalist = $list;
    }
    
    /**
     * Generates the HTML for the form element.
     * 
     * @return string
     */
    public function __toString(){
        $html = '';
        if( isset($this->datalist) ){
            $html .= $this->datalist;
            $this->attributes['list'] = $this->datalist->getId();
        }
        $html .= parent::__toString();
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
        //$maxlength = get::array_def($this->attributes, 'maxlength', 0);
        //$pattern = get::array_def($this->attributes, 'pattern', null);
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        
        if( $required && (!isset($value) || strlen(trim($value)) < 1) )
            return sprintf('"%s" is a required field.', $msglbl);
        
        //if( $maxlength > 0 && strlen(trim($value)) > $maxlength )
            //return sprintf('"%s" has to many characters - the max length is %s.', $msglbl, $maxlength);
        //if( $pattern != null && isset($value) && strlen(trim($value)) > 0 && !preg_match('/^(?:'.$pattern.')$/', $value) )
            //return sprintf('"%s" does not match the pattern defined for it.', $msglbl);
        
        return true;
    }
}