<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_url
 * Represents an HTML5 url form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_url extends fe_text{
    
    /**
     * Stores the attributes allowed for a url element.
     */
    protected static $badAttributes = array('dirname');
    
    /**
     * Creates a new url form element.
     * 
     * @param array $attributes The attributes that should be assigned to the url element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'url';
    }
    
    /**
     * Validates a url value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $return = parent::validate($value);
        if( $return !== true ) return $return;
        
        if( isset($value) && strlen(trim($value)) > 0 ){
            $valid = is::url($value, true);
            if( !is_array($valid) ){
                $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
                return sprintf('"%s" has an invalid url. Urls start with http/https/ftp followed by "://" and then the domain and path.', $msglbl);
            }
            $value = $valid;
        }
        return true;
    }
}