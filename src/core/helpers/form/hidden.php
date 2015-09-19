<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_hidden
 * Represents an HTML5 hidden form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_hidden extends fe_input{
    
    /**
     * Stores the attributes that are allowed for a hidden element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'type', 'value');
    
    /**
     * Creates a new hidden form element.
     * 
     * @param array $attributes The attributes that should be assigned to the hidden element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'hidden';
    }
    
}

