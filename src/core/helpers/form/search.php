<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_search
 * Represents an HTML5 search form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_search extends fe_text{
    
    /**
     * Creates a new search form element.
     * 
     * @param array $attributes The attributes that should be assigned to the search element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'search';
    }
    
}