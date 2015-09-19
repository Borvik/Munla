<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_tel
 * Represents an HTML5 telephone form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_tel extends fe_text{
    
    /**
     * Stores the attributes that are allowed for a tel element.
     */
    protected static $badAttributes = array('dirname');
    
    /**
     * Stores the custom attributes that are allowed for a tel element.
     */
    protected static $customAttributes = array('validatemode');
    
    /**
     * Creates a new tel form element.
     * 
     * @param array $attributes The attributes that should be assigned to the tel element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'tel';
    }
    
    /**
     * Validates a telephone value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $return = parent::validate($value);
        if( $return !== true ) return $return;
        
        $mode = get::array_def($this->attributes, 'validatemode', 'none');
        if( isset($value) && strlen(trim($value)) > 0 && $mode != 'none' ){
            if( strtolower($mode) == 'us' ){
                $return = false;
                if( preg_match('/^[\(]?(\d{0,3})[\)]?[\s]?[\-]?(\d{3})[\s]?[\-]?(\d{4})([x\s]{1,}(\d*))?$/', trim($value), $matches) ){
                    $phoneNumber = '';
                    // we have a match, dump sub-patterns to $matches
                    $phone_number = $matches[0]; // original number
                    $area_code = $matches[1];    // 3-digit area code
                    $exchange = $matches[2];     // 3-digit exchange
                    $number = $matches[3];       // 4-digit number
                    
                    $return = new phoneNumber();
                    $return->original = $matches[0];
                    if( isset($matches[1]) && strlen(trim($matches[1])) > 0 ){
                        $return->areacode = trim($matches[1]);
                        $return->formatted .= '('.$return->areacode.') ';
                    }
                    $return->exchange = $matches[2];
                    $return->number = $matches[3];
                    $return->formatted .= $return->exchange.'-'.$return->number;
                    if( isset($matches[4]) && strlen(trim($matches[4])) > 0 ){
                        $return->extension = trim($matches[5]);
                        $return->xformatted = $return->formatted.' x'.$return->extension;
                    }
                }
                
                if( $return === false ){
                    $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
                    return sprintf('"%s" has an invalid phone format (###-#### with optional 3 digit area code, and/or extension).', $msglbl);
                }
                $value = $return;
            }
        }
        return true;
    }
}