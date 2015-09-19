<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_validator
 * Defines the core of a form element that can validate.
 * 
 * @package    Framework
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
interface fe_validator{
    
    /**
     * The validating function.
     * 
     * @param mixed &$value The value to validate - passed by reference.
     * 
     * @return bool|string Returns boolean TRUE on success, or a error message upon failure.
     */
    public function validate(&$value);
    
}

/**
 * formElement
 * Defines the core of a form element.
 * 
 * @package    Framework
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class formElement{
    
    /**
     * Global attributes provided by ALL html elements.
     */
    protected static $elAttributes = array('accesskey', 'class', 'contenteditable', 'contextmenu', 'dir', 'draggable', 'dropzone', 'hidden', 'id', 'lang', 'spellcheck', 'style', 'tabindex', 'title', 'translate', 'xml:lang', 'xml:space', 'xml:base');
    protected static $badAttributes = array();
    
    /**
     * Global JavaScript attributes for ALL elements.
     */
    protected static $elJS = array('onabort', 'onblur', 'oncanplay', 'oncanplaythrough', 'onchange', 'onclick', 'oncontextmenu', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onfocus', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onloadeddata', 'onloadedmetadata', 'onloadstart', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onpause', 'onplay', 'onplaying', 'onprogress', 'onratechange', 'onreadystatechange', 'onreset', 'onscroll', 'onseeked', 'onseeking', 'onselect', 'onshow', 'onstalled', 'onsubmit', 'onsuspend', 'ontimeupdate', 'onvolumechange', 'onwaiting');
    
    /**
     * Placeholder for a list of attributes specifically for an element.
     */
    
    /**
     * Placeholder for a list of JavaScript attributes specifically for an element.
     */
    
    /**
     * List of globally allowed custom attributes.  Combined with overridden $customAttributes in element classes.
     */
    protected static $customAttributes = array('msglbl');
    
    /**
     * Stores the attributes for the form element.
     */
    protected $attributes = array();
    
    /**
     * An array to store field array names and their indicies.
     * Use to help expand multiple fields to their full names.
     * Ex.
     * 1st instance: toppings[] -> toppings[0]
     * 2nd instance: toppings[] -> toppings[1]
     */
    public static $fieldNameArrays = array();
    
    /**
     * A list of enumerated attributes and what their values should really be.
     */
    public static $enumAttributes = array(
        'contenteditable' => array('', true => 'true', false => 'false'),
        'dir' => array('auto', 'ltr', 'rtl'),
        'draggable' => array('auto', true => 'true', false => 'false'),
        'dropzone' => array('copy', 'move', 'link', 'copy move', 'copy link', 'move copy', 'move link', 'link copy', 'link move', 'copy move link', 'copy link move', 'move copy link', 'move link copy', 'link copy move', 'link move copy'),
        'spellcheck' => array('', true => 'true', false => 'false'),
        'translate' => array(true => 'yes', false => 'no'),
        'xml:space' => array('default', 'preserve'),
        'formenctype' => array('text/plain', 'multipart/form-data', 'application/x-www-form-urlencoded'),
        'formmethod' => array('get', 'post'),
        'autocomplete' => array('', true => 'on', false => 'off'),
        'wrap' => array('hard', 'soft'),
        'formtarget' => array('_blank', '_self', '_parent', '_top'),
        'target' => array('_blank', '_self', '_parent', '_top'),
        );
    
    /**
     * A list of boolean attributes.
     */
    public static $boolAttributes = array(
        'autofocus', 'checked', 'disabled', 'formnovalidate', 'hidden', 'multiple', 'readonly', 'required', 
        );
    
    /**
     * Stores the next form element ID number (for use when generating element ID when they aren't supplied).
     */
    protected static $nextIdNum = 0;
    
    /**
     * Stores the ID attribute for the element.  This is separate from the other attributes for instances where it needs to be generated.
     */
    protected $id = null;
    
    /**
     * Stores the name attribute for the element.  This is separate from the other attributes for instances where it needs to be generated.
     */
    protected $name = null;
    
    /**
     * Creates a new form element.
     * 
     * @param array $attributes The attributes that should be assigned to the input element.
     */
    public function __construct(array $attributes){
        if( is::existset($attributes, 'id') )
            $this->setId($attributes['id']);
        if( is::existset($attributes, 'name') )
            $this->setName($attributes['name']);
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
        
        $attributes = array(); $badAtt = array(); $last = array('att' => array(), 'badAtt' => array(), 'custom' => array(), 'js' => array());
        foreach($chain as $class){
            //if( $class == 'formElement' ){ $last['js'] = $class::$elJS; $last['att'] = $class::$elAttributes; $last['badAtt'] = $class::$badAttributes; $last['custom'] = $class::$customAttributes; continue; }
            if( $last['badAtt'] != $class::$badAttributes ) $attributes = array_diff($attributes, $class::$badAttributes);
            if( $last['att'] != $class::$elAttributes ) $attributes = array_merge($attributes, $class::$elAttributes);
            if( $last['custom'] != $class::$customAttributes ) $attributes = array_merge($attributes, $class::$customAttributes);
            if( $last['js'] != $class::$elJS ) $attributes = array_merge($attributes, $class::$elJS);
            
            $last['att'] = $class::$elAttributes;
            $last['badAtt'] = $class::$badAttributes;
            $last['custom'] = $class::$customAttributes;
            $last['js'] = $class::$elJS;
        }
        $attributes = array_values(array_unique($attributes));
        return $attributes;
    }
    
    public static function customAttributes(){
        $class = get_called_class();
        $chain = array($class);
        while($class = get_parent_class($class)) $chain[] = $class;
        $chain = array_reverse($chain);
        
        $attributes = array(); $last = array();
        foreach($chain as $class){
            if( $last != $class::$customAttributes ) $attributes = array_merge($attributes, $class::$customAttributes);
            $last = $class::$customAttributes;
        }
        $attributes = array_values(array_unique($attributes));
        return $attributes;
    }
    
    /**
     * Gets the attributes of the form element that are ok to output to the browser. Basically ALL but the custom attributes.
     * 
     * @return array
     */
    public function getAttributes(){
        $class = get_class($this);
        $a = array_filter($this->attributes, function($v){ return !is_null($v); });
        //$c = array_merge($class::$customAttributes, formElement::$customAttributes);
        $c = $class::customAttributes();
        //return array_intersect_key($a, array_flip(array_filter(array_keys($a), function($k){ return !in_array($k, $c); }))); //return ($k != 'msglbl');
        return array_intersect_key($a, array_flip(array_filter(array_keys($a), array(new GetAttributeFilter($c), 'doFilter'))));
    }
    
    /**
     * Set a specific attribute for a form element.
     * 
     * @param string $name The name of the attribute to set.
     * @param mixed $value The value of the attribute.
     */
    public function setAttribute($name, $value){
        $this->attributes[$name] = $value;
    }
    
    /**
     * Get a specific attribute from this form element.
     * 
     * @param string $name The name of the attribute to get.
     * 
     * @return mixed
     */
    public function getAttibuteValue($name){
        return get::array_def($this->attributes, $name);
    }
    
    /**
     * Returns a fallback script to set the focus to the given element id.
     * 
     * @param string $id The ID of the form element to set the focus to.
     * 
     * @return string
     */
    protected function getAutoFocusScript($id){
        return sprintf('<script type="text/javascript"> if (!("autofocus" in document.createElement("input"))) { document.getElementById("%s").focus(); } </script>', $id);
    }
    
    /**
     * Sets the form element's id attribute.
     * 
     * @param string $id The ID of the form element.
     * 
     * @return void
     */
    public function setId($id){ $this->id = $id; }
    
    /**
     * Gets the id attribute of the form element, or creates one if it hasn't been set.
     * 
     * @return string
     */
    public function getId(){
        if( !isset($this->id) ) $this->id = sprintf('formInput_%d', ++self::$nextIdNum);
        return $this->id;
    }
    
    /**
     * Gets whether this field had an error during form processing.
     * 
     * @return bool True if the field has an error, false otherwise.
     */
    public function hasError(){
        $id = $this->getId();
        return get::helper('form')->hasFieldError($id);
    }
    
    /**
     * Sets the name attribute for the form element.
     * 
     * @param string $name The name of the form element.
     * 
     * @return void
     */
    public function setName($name){ $this->name = self::getFieldPath($name); }
    
    /**
     * Gets the name attribute of the form element, or creates one from the ID if it hasn't already been set.
     * 
     * @return string
     */
    public function getName(){
        if( !isset($this->name) ) $this->setName($this->getId());
        return $this->name;
    }
    
    /**
     * Gets the value of this field from the given request array (GET/POST).
     * 
     * @param array $input The request array to get the value from.
     * 
     * @return mixed Returns a reference to the value that was requested of it, or null if it was unable to find the value.
     */
    public function &getValue(array &$input){
        $null = null;
        if( !isset($input) || count($input) < 1 ) return $null;
        
        //expand the name to it's full path (ex. fieldName[0][3] becomes array(fieldname, 0, 3))
        $namepath = self::getFieldPath($this->getName(), false, false);
        if( !isset($namepath) || !is_array($namepath) || count($namepath) < 1 ) return $null;
        
        $arr = &$input;
        foreach($namepath as $idx){
            if( !is_array($arr) || !array_key_exists($idx, $arr) ){
                //value not found
                unset($arr);
                break;
            }else{
                $arr = &$arr[$idx];
            }
        }
        
        return $arr;
    }
    
    /**
     * Expands a field name array out so that all indicies are explicitly stated.
     * 
     * @param string $name
     *   The condensed name of the field to expand.
     * 
     * @param boolean $flatten
     *   A boolean to determine if the name should be flattened and returned as a string
     *   or left as an array for traversal.
     * 
     * @param boolean $buildDynamic
     *   A boolean to determine if missing indicies should be generated or not.
     * 
     * @return mixed
     *   When flattened it returns the name array as a string - suitable for outputting
     *   in HTML. Otherwise it returns an array with the name, and the indicies following
     *   it to be used for loops.
     */
    public static function getFieldPath($name, $flatten = true, $buildDynamic = true){
        if( !isset($name) ) return null;
        
        $return = array();
        $lpos = strpos($name, '[');
        $rpos = strpos($name, ']');
        if( $lpos === false || $rpos === false ){
            // straight name - though first [ should be an _
            if( $lpos ) $name = preg_replace('/\[/', '_', $name, 1);
            $return = array($name);
        }else{
            $nextRpos = strpos($name, ']', $lpos);
            if( $nextRpos === false ) $return = array(substr_replace($name, '_', $lpos, 1));
            else{
                $nextLpos = strpos($name, '[', $nextRpos);
                while($nextLpos !== false){
                    if( $nextLpos > $nextRpos + 1 ) break;
                    $prevRpos = $nextRpos;
                    $nextRpos = strpos($name, ']', $nextLpos);
                    if( $nextRpos === false ){
                        $nextRpos = $prevRpos;
                        break;
                    }
                    $nextLpos = strpos($name, '[', $nextRpos);
                }
                $name = substr($name, 0, $nextRpos + 1);
                if( preg_match('/^([^\[]*)\[/', $name, $matches) ){
                    $return[] = $matches[1];
                    if( preg_match_all('/\[(.*?)\]/', $name, $matches) ){
                        foreach($matches[1] as $idx){
                            if( $buildDynamic ) $idx = self::getNextIndex($return, $idx, self::$fieldNameArrays);
                            $return[] = $idx;
                        }
                    }
                }
            }
        }
        if( count($return) > 0 ){
            array_unshift($return, preg_replace('/[^a-zA-Z0-9_\x7f-\xff\[\]\-]/', '_', array_shift($return)));
        }
        if( $flatten ){
            $name = array_shift($return);
            if( count($return) > 0 )
                $name .= '['.implode('][', $return).']';
            $return = $name;
        }
        return $return;
    }
    
    /**
     * Gets the next numeric index for an HTML input array.
     * 
     * @param array $names
     *   An array containing the current build of the field name array.
     * 
     * @param mixed $next
     *   Defaults to an empty string. The next index specified in the name.
     *   Can be something other than an integer, it won't be incremented and is safe to return
     *   as the next index. When it is numeric it resets the index.
     * 
     * @param array $arr
     *   The array that should store all the indicies in the form.
     * 
     * @return int
     *   Returns the next numeric index number for a HTML input array.
     */
    private static function getNextIndex(array $names, $next = '', array &$arr){
        $idx = strval(array_shift($names));
        if( count($names) == 0 ){
            if( !array_key_exists($idx, $arr) )
                $arr[$idx] = array('...' => -1);
            if( strlen($next) < 1 )
                return ++$arr[$idx]['...'];
            if( ctype_digit($next) && (int)$next > $arr[$idx]['...'] ){
                $arr[$idx]['...'] = (int)$next;
                return (int)$next;
            }
            return $next;
        }else{
            if( !array_key_exists($idx, $arr) )
                $arr[$idx] = array('...' => -1);
            if( ctype_digit($idx) && (int)$idx > $arr[$idx]['...'] )
                $arr[$idx]['...'] = (int)$idx;
            return self::getNextIndex($names, $next, $arr[$idx]);
        }
    }
    
    /**
     * Must implement magic method __toString to allow elements to easily be output to the browser.
     * 
     * @return string
     */
    abstract public function __toString();
}

/**
 * GetAttributeFilter
 * Used in formElement::getAttributes with array_filter to help filter out all elements in an array.
 * 
 * @package    Framework
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class GetAttributeFilter{
    
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