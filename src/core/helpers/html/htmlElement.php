<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * htmlElement
 * Defines the core of a html element.
 * 
 * @package    Framework
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class htmlElement{
    
    /**
     * Global attributes provided by ALL html elements.
     */
    protected static $elAttributes = array('accesskey', 'class', 'contenteditable', 'contextmenu', 'dir', 'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck', 'style', 'tabindex', 'title', 'translate');
    protected static $badAttributes = array();
    
    /**
     * Global JavaScript attributes for ALL elements.
     */
    protected static $elJS = array('onabort', 'onblur', 'oncanplay', 'oncanplaythrough', 'onchange', 'onclick', 'oncontextmenu', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onfocus', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onloadeddata', 'onloadedmetadata', 'onloadstart', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpause', 'onplay', 'onplaying', 'onprogress', 'onratechange', 'onreadystatechange', 'onscroll', 'onseeked', 'onseeking', 'onselect', 'onshow', 'onstalled', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting');
    
    /**
     * A list of enumerated attributes and what their values should really be.
     */
    public static $enumAttributes = array(
        'contenteditable' => array('', true => 'true', false => 'false'),
        'dir' => array('ltr', 'rtl', 'auto'),
        'draggable' => array('auto', true => 'true', false => 'false'),
        'dropzone' => array('copy', 'move', 'link', 'copy move', 'copy link', 'move copy', 'move link', 'link copy', 'link move', 'copy move link', 'copy link move', 'move copy link', 'move link copy', 'link copy move', 'link move copy'),
        'spellcheck' => array('', true => 'true', false => 'false'),
        'translate' => array(true => 'yes', false => 'no'),
        );
    
    /**
     * A list of boolean attributes.
     */
    public static $boolAttributes = array(
        'hidden', 'multiple', 'readonly', 'required', 'ismap', 
        );
    
    /**
     * Stores the attributes for the element.
     */
    protected $attributes = array();
    
    /**
     * Creates a new element.
     * 
     * @param array $attributes The attributes that should be assigned to the element.
     */
    public function __construct(array $attributes){
        $this->attributes = $attributes;
    }
    
    /**
     * Returns a list of all the attributes that will be accepted for a given element.
     * 
     * @return array
     */
    public static function acceptedAttributes(){
        $class = get_called_class();
        $chain = array($class);
        while($class = get_parent_class($class)) $chain[] = $class;
        $chain = array_reverse($chain);
        
        $attributes = array(); $badAtt = array(); $last = array('att' => array(), 'badAtt' => array(), 'js' => array());
        foreach($chain as $class){
            //if( $class == 'formElement' ){ $last['js'] = $class::$elJS; $last['att'] = $class::$elAttributes; $last['badAtt'] = $class::$badAttributes; $last['custom'] = $class::$customAttributes; continue; }
            if( $last['badAtt'] != $class::$badAttributes ) $attributes = array_diff($attributes, $class::$badAttributes);
            if( $last['att'] != $class::$elAttributes ) $attributes = array_merge($attributes, $class::$elAttributes);
            if( $last['js'] != $class::$elJS ) $attributes = array_merge($attributes, $class::$elJS);
            
            $last['att'] = $class::$elAttributes;
            $last['badAtt'] = $class::$badAttributes;
            $last['js'] = $class::$elJS;
        }
        $attributes = array_values(array_unique($attributes));
        return $attributes;
    }
    
    /**
     * Gets the attributes of the element.
     * 
     * @return array
     */
    public function getAttributes(){
        $a = array_filter($this->attributes, function($v){ return !is_null($v); });
        return $a;
    }
    
    /**
     * Must implement magic method __toString to allow elements to easily be output to the browser.
     * 
     * @return string
     */
    abstract public function __toString();
    
}

/**
 * HtmlAttributeFilter
 * Used in htmlElement::getAttributes with array_filter to help filter out all elements in an array.
 * 
 * @package    Framework
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class HtmlAttributeFilter{
    
    /**
     * Creates a new instance of the GetAttributeFilter.
     * 
     * @param array $filter An array to use as the filter.  All elements in this array will be removed from the array being filtered.
     */
    function __construct(array $filter){ $this->filter = $filter; }
    
    /**
     * Callback function for array_filter to filter out elements in the other array.
     * 
     * @param mixed $k The value being checked.
     * 
     * @return bool Return FALSE to remove a element. If in this filter array (in_array = true) we must negate this to remove the element.
     */
    function doFilter($k){ return !in_array($k, $this->filter); }
}