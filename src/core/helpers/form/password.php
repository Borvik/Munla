<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_password
 * Represents an HTML5 password form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_password extends fe_text{
    
    /**
     * Stores the attributes that are allowed for a password element.
     */
    protected static $badAttributes = array('dirname', 'list');
    
    /**
     * Creates a new password form element.
     * 
     * @param array $attributes The attributes that should be assigned to the password element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'password';
        if( isset($this->attributes['value']) ) unset($this->attributes['value']);
    }
    
}