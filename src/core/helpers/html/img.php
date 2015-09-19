<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * he_img
 * Represents an image element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class he_img extends htmlElement{
    
    /**
     * Stores the attributes allowed for a this element.
     */
    protected static $elAttributes = array('src', 'alt', 'height', 'width', 'usmap', 'ismap');
    
    /**
     * Creates a new image element.
     * 
     * @param array $attributes The attributes that should be assigned to the element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
    }
    
    /**
     * Generates the HTML for the image element.
     * 
     * @return string
     */
    public function __toString(){
        return sprintf('<img%s />', get::formattedAttributes($this->getAttributes()));
    }
    
}
