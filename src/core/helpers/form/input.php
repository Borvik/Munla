<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_input
 * Represents an HTML5 input form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class fe_input extends formElement{
    
    //protected static $elAttributes = array();
    /**
     * Stores the type attribute of the form element separately.
     */
    protected $type = null;
    
    /**
     * Generates the HTML for the form element.
     * 
     * @return string
     */
    public function __toString(){
        $this->attributes['id'] = $this->getId();
        $this->attributes['name'] = $this->getName();
        $this->attributes['type'] = $this->type;
        $html = sprintf('<input%s />', get::formattedAttributes($this->getAttributes()));
        if( is::existset($this->attributes, 'autofocus') )
            $html .= $this->getAutoFocusScript($this->attributes['id']);
        return $html;
    }
}

