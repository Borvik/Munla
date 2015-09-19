<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_text
 * Represents an HTML5 text form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_text extends fe_textbox{
    
    /**
     * Stores the attributes that are allowed for a text element.
     */
    protected static $elAttributes = array('maxlength', 'pattern', 'size', 'dirname');
    
    /**
     * Validates a text value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $return = parent::validate($value);
        if( $return !== true ) return $return;
        
        $maxlength = get::array_def($this->attributes, 'maxlength', 0);
        $pattern = get::array_def($this->attributes, 'pattern', null);
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        
        if( $maxlength > 0 && strlen(trim($value)) > $maxlength )
            return sprintf('"%s" has to many characters - the max length is %s.', $msglbl, $maxlength);
        if( $pattern != null && isset($value) && strlen(trim($value)) > 0 && !preg_match('/^(?:'.$pattern.')$/', $value) )
            return sprintf('"%s" does not match the pattern defined for it.', $msglbl);
        
        return true;
    }
}