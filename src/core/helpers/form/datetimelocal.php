<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_datetimelocal
 * Represents an HTML5 url datetime-local element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_datetimelocal extends fe_textboxnumeric{
    
    /**
     * Stores the attributes that are allowed for a datetime-local element.
     */
    protected static $badAttributes = array('placeholder');
    
    /**
     * Stores the custom attributes that are allowed for a tel element.
     */
    protected static $customAttributes = array('datemode');
    
    /**
     * Creates a new datetime-local element.
     * 
     * @param array $attributes The attributes that should be assigned to the datetime-local element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'datetime-local';
    }
    
    /**
     * Generates the HTML for the form element.
     * 
     * @return string
     */
    public function __toString(){
        $min = get::array_def($this->attributes, 'min', false);
        $max = get::array_def($this->attributes, 'max', false);
        if( $min && (!is_object($min) || !($min instanceof cDateTime)) && $min = date_create($min) )
            $this->attributes['min'] = new cDateTime(date_create($min->format('Y-m-d\\TH:i:s.uP')), cDateTime::DATETIMELOCAL_KIND);
        if( $max && (!is_object($max) || !($max instanceof cDateTime)) && $max = date_create($max) )
            $this->attributes['max'] = new cDateTime(date_create($max->format('Y-m-d\\TH:i:s.uP')), cDateTime::DATETIMELOCAL_KIND);
        return parent::__toString();
    }
    
    /**
     * Validates a datetimelocal value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $return = parent::validate($value);
        if( $return !== true ) return $return;
        
        if( isset($value) && strlen(trim($value)) > 0 ){
            $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
            $valid = is::datetime($value, true);
            if( $valid === false )
                return sprintf('"%s" does not have a valid date/time. The format is YYYY-mm-ddThh:mm:ss.fff. "T" is a literal separator.', $msglbl);
            
            $mode = get::array_def($this->attributes, 'datemode', 'html');
            $min = get::array_def($this->attributes, 'min', false);
            if( is_object($min) && $min instanceof cDateTime ){
                if( $valid->lessThan($min) ){
                    switch($mode){
                        case 'html': break;
                        case 'us': $min = $min->format_us(); break;
                        default: $min = $min->format($mode); break;
                    }
                    return sprintf('"%s" cannot be before "%s".', $msglbl, $min);
                }
            }
            
            $max = get::array_def($this->attributes, 'max', false);
            if( is_object($max) && $max instanceof cDateTime ){
                if( $max->lessThan($valid) ){
                    switch($mode){
                        case 'html': break;
                        case 'us': $max = $max->format_us(); break;
                        default: $max = $max->format($mode); break;
                    }
                    return sprintf('"%s" cannot be after "%s".', $msglbl, $max);
                }
            }
            $value = $valid;
        }
        return true;
    }
}