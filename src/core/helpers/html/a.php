<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * he_a
 * Represents an anchor element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class he_a extends htmlElement{
    
    /**
     * Stores the attributes allowed for a this element.
     */
    protected static $elAttributes = array('href', 'target', 'rel', 'hreflang', 'media', 'type');
    
    /**
     * Store the content of the anchor attribute
     */
    private $content = '';
    
    /**
     * Creates a new anchor element.
     * 
     * @param array $attributes The attributes that should be assigned to the element.
     */
    public function __construct($content, array $attributes){
        parent::__construct($attributes);
        $this->content = $content;
    }
    
    public function get_content(){ return $this->content; }
    public function get_href(){ return $this->attributes['href']; }
    
    /**
     * Generates the HTML for the anchor element.
     * 
     * @return string
     */
    public function __toString(){
        $html = '';
        //if( strlen((string)$this->content) > 0 )
            $html = sprintf('<a%s>%s</a>', get::formattedAttributes($this->getAttributes()), $this->content);
        //else
            //$html = sprintf('<a%s />', get::formattedAttributes($this->getAttributes()));
        return $html;
    }
    
}
