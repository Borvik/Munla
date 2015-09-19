<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_range
 * Represents an HTML5 range form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_range extends fe_number{
    
    /**
     * Stores the attributes that are allowed for a range element.
     */
    protected static $badAttributes = array('readonly', 'required', 'placeholder');
    
    /**
     * Creates a new range form element.
     * 
     * @param array $attributes The attributes that should be assigned to the range element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'range';
    }
    
}