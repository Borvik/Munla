<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_textboxnumeric
 * Represents an HTML5 numeric textbox - used as the basis for other numeric based fields (including dates).
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class fe_textboxnumeric extends fe_textbox{
    
    /**
     * Stores the attributes that are allowed for a text element.
     */
    protected static $elAttributes = array('min', 'max', 'step');
    protected static $elementAttributes = array('name', 'autocomplete', 'dirname', 'list', 'maxlength', 'pattern', 'placeholder', 'readonly', 'required', 'size', 'value');
    
}