<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_button
 * Represents an HTML5 button form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_button extends formElement{
    
    /**
     * Stores the attributes allowed for a button.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'type', 'value', 'autofocus');
    
    /**
     * Stores the custom attributes allowed for a button.
     */
    protected static $customAttributes = array('content');
    
    /**
     * Stores the type attribute of the form element separately.
     */
    protected $type = null;
    
    /**
     * Creates a new form element.
     * 
     * @param array $attributes The attributes that should be assigned to the input element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'button';
    }
    
    /**
     * Generates the HTML for the form element.
     * 
     * @return string
     */
    public function __toString(){
        $this->attributes['id'] = $this->getId();
        $this->attributes['name'] = $this->getName();
        $this->attributes['value'] = get::array_def($this->attributes, 'value', '');
        $this->attributes['type'] = $this->type;
        $content = get::array_def($this->attributes, 'content', $this->attributes['value']);
        $html = sprintf('<button%s>%s</button>', get::formattedAttributes($this->getAttributes()), $content);
        if( is::existset($this->attributes, 'autofocus') )
            $html .= $this->getAutoFocusScript($this->attributes['id']);
        return $html;
    }
}