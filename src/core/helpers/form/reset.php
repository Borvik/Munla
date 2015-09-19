<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_reset
 * Represents an HTML5 reset form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_reset extends fe_button{
    
    /**
     * Stores the attributes that are allowed for a reset button.
     */
    protected static $elementAttributes = array('name', 'value');
    
    /**
     * Stores the custom attributes that are allowed for a reset button.
     */
    protected static $customAttributes = array('content');
    
    /**
     * Creates a new reset form element.
     * 
     * @param array $attributes The attributes that should be assigned to the reset element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'reset';
    }
    
}