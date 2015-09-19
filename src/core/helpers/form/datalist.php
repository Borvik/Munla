<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_datalist
 * Represents an HTML5 datalist form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_datalist extends formElement{
    
    /**
     * Stores the list of data elements.
     */
    protected $list = array();
    
    /**
     * Creates a new datalist element.
     * 
     * @param array $list The list of elements to create.
     */
    public function __construct(array $list){
        if( isset($list) ) $this->list = $list;
    }
    
    /**
     * Creates the HTML for the datalist element.
     * 
     * @return string
     */
    public function __toString(){
        $html = sprintf('<datalist id="%s">', get::encodedAttribute($this->getId()));
        if( is::assoc_array($this->list) ){
            foreach($this->list as $value => $lbl)
                $html .= sprintf('<option label="%s" value="%s" />', get::encodedAttribute($lbl), get::encodedAttribute($value));
        }else{
            foreach($this->list as $value)
                $html .= sprintf('<option value="%s" />', get::encodedAttribute($value));
        }
        $html .= '</datalist>';
        
        return $html;
    }
}