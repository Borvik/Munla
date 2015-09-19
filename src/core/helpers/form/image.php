<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_image
 * Represents an HTML5 image form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_image extends fe_input{
    
    /**
     * Stores the attributes that are allowed for an image element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'type', 'alt', 'src', 'formaction', 'autofocus', 'formenctype', 'formmethod', 'formtarget', 'formnovalidate', 'height', 'width', 'value');
    
    /**
     * Creates a new text form element.
     * 
     * @param array $attributes The attributes that should be assigned to the text element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'image';
        if( array_key_exists('value', $this->attributes) && !array_key_exists('src', $this->attributes) ){
            $this->attributes['src'] = $this->attributes['value'];
            unset($this->attributes['value']);
        }
    }
    
}