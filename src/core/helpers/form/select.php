<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_select
 * Represents an HTML5 select form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_select extends formElement implements fe_validator{
    
    /**
     * Stores the attributes that are allowed for a select element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'size', 'multiple', 'autofocus', 'required');
    
    /**
     * Stores the custom attributes that are allowed for a select element.
     */
    protected static $customAttributes = array('value', 'keyvalues', 'placeholder', 'separator', 'allowchange');
    
    /**
     * Stores the list of options for a select element.
     */
    private $list = null;
    
    /**
     * Stores a list of all the acceptable values for the select element.  Populated when the list is set.
     */
    private $values = array();
    
    /**
     * Stores the value associated with an empty selection (necessary to carry through the session to validation time).
     */
    private $emptyValue = null;
    
    /**
     * Stores the value associated with an empty selection (used during generation to ensure the entire page is using the same value for empty select - simplifies things).
     */
    private static $shareEmptyValue = null;
    
    /**
     * Creates a new select form element.
     * 
     * @param array $attributes The attributes that should be assigned to the select element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        //if( is_null(fe_select::$shareEmptyValue) ) fe_select::$shareEmptyValue = get::rand_string(8);
        //$this->emptyValue = fe_select::$shareEmptyValue;
        $this->emptyValue = '';
    }
    
    /**
     * Sets the option list associated with this select element.
     * 
     * @param array $list The option list.
     * 
     * @return void
     */
    public function setList(array $list){
        $this->list = $list;
        $this->values = array();
        if( isset($this->list) && count($this->list) > 0 ){
            $keyvalues = get::array_def($this->attributes, 'keyvalues', false, array(true, false));
            $stack = $this->build_stack($this->list);
            while($stack){
                $current = array_shift($stack);
                if( is_array($current['value']) ){
                    if( !is_string($current['key']) ) continue;
                    $substack = $this->build_stack($current['value']);
                    if( $substack ){
                        while($substack)
                            array_unshift($stack, array_pop($substack));
                    }
                }else{
                    $v = ($keyvalues || is_string($current['key'])) ? (string)$current['key'] : $current['value'];
                    if( $current['value'] != '-' )
                        $this->values[] = $v;
                }
            }
        }
    }
    
    /**
     * Generates the HTML for the form element.
     * 
     * @return string
     */
    public function __toString(){
        $this->attributes['id'] = $this->getId();
        $this->attributes['name'] = $this->getName();
        
        $keyvalues = get::array_def($this->attributes, 'keyvalues', false, array(true, false));
        $placeholder = get::array_def($this->attributes, 'placeholder', '');
        $separator = get::array_def($this->attributes, 'separator', false);
        $value = get::array_def($this->attributes, 'value', array());
        $multiple = (get::array_def($this->attributes, 'multiple', false) == 'multiple');
        if( $multiple && substr($this->attributes['name'], -2) != '[]' ) $this->attributes['name'] .= '[]';
        
        if( !is_array($value) && strlen(trim($value)) < 1 ) $value = array();
        if( !is_array($value) ) $value = array($value);
        $html = sprintf('<select%s>', get::formattedAttributes($this->getAttributes()));
        if( !$multiple && isset($placeholder) && strlen(trim($placeholder)) > 0 ){
            $html .= sprintf('<option value="%s" disabled%s>%s</option>', get::encodedAttribute($this->emptyValue), (count($value) > 0 ? '' : ' selected'), get::entities($placeholder));
            if( isset($separator) && $separator !== false ){
                $separator = ($separator === true) ? '---------------' : $separator;
                $html .= sprintf('<option value="%s" disabled>%s</option>', get::encodedAttribute($this->emptyValue), get::entities($separator));
            }
        }
        if( isset($this->list) && count($this->list) > 0 ){
            $stack = $this->build_stack($this->list);
            while($stack){
                $current = array_shift($stack);
                if( !is_array($current) ){ $html .= $current; continue; }
                if( is_array($current['value']) ){
                    if( !is_string($current['key']) ) continue;
                    $substack = $this->build_stack($current['value']);
                    if( $substack ){
                        $html .= sprintf('<optgroup label="%s">', get::encodedAttribute($current['key']));
                        array_unshift($stack, '</optgroup>');
                        while($substack)
                            array_unshift($stack, array_pop($substack));
                    }
                }else{
                    $v = ($keyvalues || is_string($current['key'])) ? (string)$current['key'] : $current['value'];
                    $l = $current['value'];
                    $selected = '';
                    if( $l == '-' ){
                        $l = (!isset($separator) || is_bool($separator)) ? '---------------' : $separator;
                        $v = $this->emptyValue;
                    }else
                        $selected = (in_array($v, $value) ? ' selected="selected"' : '');
                    $html .= sprintf('<option value="%s"%s>%s</option>', get::encodedAttribute($v), $selected, get::entities($l));
                }
            }
        }
        $html .= '</select>';
        if( is::existset($this->attributes, 'autofocus') )
            $html .= $this->getAutoFocusScript($this->attributes['id']);
        return $html;
    }
    
    /**
     * Builds a key/value stack out of the passed array.
     * 
     * Helps for building recursive-less generation of nested option groups.
     * 
     * @param array $arr The option list to generate the stack for.
     * 
     * @return array
     */
    private function build_stack(array $arr){
        $stack = array();
        foreach($arr as $k => $v)
            $stack[] = array('key' => $k, 'value' => $v);
        return $stack;
    }
    
    /**
     * Validates a selected value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        if( is_array($value) ) $value = array_filter($value, array($this, 'removeEmptyValues'));
        elseif( $value == $this->emptyValue ) $value = null;
        
        $allowchange = get::array_def($this->attributes, 'allowchange', false, array(true, false));
        $required = (get::array_def($this->attributes, 'required', false) == 'required');
        $multiple = (get::array_def($this->attributes, 'multiple', false) == 'multiple');
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        
        if( $required && (!isset($value) || (!is_array($value) && (strlen(trim($value)) < 1 || $value == $this->emptyValue)) || (is_array($value) && count($value) < 1)) )
            return sprintf('"%s" is a required field.', $msglbl);
        elseif( !$allowchange ){
            if( !$multiple && isset($value) && is_array($value) )
                return sprintf('Multiple values are not allowed for "%s" - please choose from the list.', $msglbl);
            if( isset($value) && !is_array($value) && $multiple )
                $value = array($value);
            
            if( isset($value) && !is_array($value) && !in_array($value, $this->values) )
                return sprintf('"%s" is not a valid value for "%s" - please choose from the list.', $value, $msglbl);
            if( isset($value) && is_array($value) ){
                foreach($value as $v){
                    if( is_array($v) )
                        return sprintf('"%s" - a select list selection may not itself be an array - please choose from the list.', $msglbl);
                    if( !in_array($v, $this->values) )
                        return sprintf('"%s" is not a valid value for "%s" - please choose from the list.', $v, $msglbl);
                }
            }
        }
        return true;
    }
    
    /**
     * Callback function for array_filter to eliminate any values that equal the assigned "empty" value.
     * 
     * @param mixed $var The value being checked.
     * 
     * @return bool Returns FALSE to remove the element.
     */
    private function removeEmptyValues($var){ return !($var == $this->emptyValue); }
}