<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_color
 * Represents an HTML5 color form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_color extends fe_textbox{
    
    /**
     * Stores the attributes that are allowed for a color element.
     */
    protected static $badAttributes = array('readonly', 'required', 'placeholder');
    
    /**
     * Creates a new color form element.
     * 
     * @param array $attributes The attributes that should be assigned to the color element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'color';
    }
    
    public function &getValue(array &$input){
        $return = parent::getValue($input);
        if( !isset($return) || strlen(trim($return)) < 1){
            $namepath = parent::getFieldPath($this->getName(), false, false);
            if( !isset($namepath) || !is_array($namepath) || count($namepath) < 1 ) return $return;
            
            $arr = &$input; $lastIdx = null; $lastArr = null;
            foreach($namepath as $idx){
                $lastIdx = $idx;
                $lastArr = &$arr;
                if( !array_key_exists($idx, $arr) )
                    $arr[$idx] = array();
                elseif( !is_array($arr[$idx]) ){
                    $lastIdx = null;
                    break;
                }
                $arr = &$arr[$idx];
            }
            if( isset($lastIdx) && isset($lastArr) ){
                $lastArr[$lastIdx] = '#000000';
                $return = &$lastArr[$lastIdx];
            }
        }
        return $return;
    }
    
    /**
     * Validates a color value.  According to the W3C this field should ALWAYS have a value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $return = parent::validate($value);
        if( $return !== true ) return $return;
        
        if( !isset($value) || strlen(trim($value)) < 1 ) $value = '#000000';
        
        if( isset($value) && strlen(trim($value)) > 0 ){
            $valid = is::color($value);
            if( $valid === false ){
                $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
                return sprintf('"%s" has an invalid color (ex. #FFFFFF).', $msglbl);
            }
            $value = $valid;
        }
        return true;
    }
}