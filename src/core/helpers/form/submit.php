<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_submit
 * Represents an HTML5 submit form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_submit extends fe_button{
    
    /**
     * Stores the attributes that are allowed for a submit button element.
     */
    protected static $elAttributes = array('formaction', 'formenctype', 'formmethod', 'formtarget', 'formnovalidate');
    protected static $elementAttributes = array('name', 'formaction', 'formmethod', 'formnovalidate', 'formtarget', 'value');
    
    /**
     * Stores the custom attributes that are allowed for a submit button element.
     */
    protected static $customAttributes = array('content');
    
    /**
     * Creates a new submit form element.
     * 
     * @param array $attributes The attributes that should be assigned to the submit element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'submit';
    }
    
}