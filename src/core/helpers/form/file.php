<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * fe_file
 * Represents an HTML5 file form element.
 * 
 * @package    Munla
 * @subpackage core\helpers\form
 * @author     Chris Kolkman
 * @version    1.0
 */
class fe_file extends fe_input implements fe_validator{
    
    /**
     * Stores the attributes that are allowed for a file element.
     */
    protected static $elAttributes = array('name', 'disabled', 'form', 'type', 'accept', 'autofocus', 'required', 'multiple');
    
    /**
     * Creates a new file form element.
     * 
     * @param array $attributes The attributes that should be assigned to the file element.
     */
    public function __construct(array $attributes){
        parent::__construct($attributes);
        $this->type = 'file';
    }
    
    /**
     * Generates the HTML for the form element.
     * 
     * @return string
     */
    public function __toString(){
        $this->attributes['id'] = $this->getId();
        $this->attributes['name'] = $this->getName();
        $this->attributes['type'] = $this->type;
        $multiple = (get::array_def($this->attributes, 'multiple', false) == 'multiple');
        if( $multiple && substr($this->attributes['name'], -2) != '[]' ) $this->attributes['name'] .= '[]';
        $html = sprintf('<input%s />', get::formattedAttributes($this->getAttributes()));
        if( is::existset($this->attributes, 'autofocus') )
            $html .= $this->getAutoFocusScript($this->attributes['id']);
        return $html;
    }
    
    /**
     * Validates a file value.
     * 
     * @param string $value The value to validate.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    public function validate(&$value){
        $required = (get::array_def($this->attributes, 'required', false) == 'required');
        $multiple = (get::array_def($this->attributes, 'multiple', false) == 'multiple');
        $msglbl = get::array_def($this->attributes, 'msglbl', get::array_def($this->attributes, 'name', $this->getId()));
        
        if( $required && (!isset($value) || $value['error'] == 4) )
            return sprintf('"%s" is a required field.', $msglbl);
        
        if( isset($value) ){
            if( $multiple ){
                foreach($value as &$file){
                    $err = $this->checkFileForError($file);
                    if( $err !== true ) return $err;
                }
            }else{
                $err = $this->checkFileForError($value);
                if( $err !== true ) return $err;
            }
        }
        return true;
    }
    
    /**
     * Checks a given file array for errors.
     * 
     * @param array $file The fixed file array from $_FILES.
     * 
     * @return bool|string Returns boolean TRUE upon successfull validation, and an error message string upon failure.
     */
    private function checkFileForError(array &$file){
        if( !in_array($file['error'], array(0, 4)) ){
            switch($file['error']){
                case 1: $return = 'The uploaded file(%1$s) exceeds the servers max filesize.'; break;
                case 2: $return = 'The uploaded file(%1$s) exceeds the max filesize.'; break;
                case 3: $return = 'The uploaded file(%1$s) was only partially uploaded.'; break;
                case 4: $return = 'No file was uploaded.'; break;
                case 6: $return = 'Missing a temporary folder.'; break;
                case 7: $return = 'Failed to write file(%1$s) to disk.'; break;
                case 8: $return = 'The uploaded file(%1$s) was stopped.'; break;
                default: $return = 'Unknown file upload error (%2$d).'; break;
            }
            return sprintf($return, $file['name'], $file['error']);
        }
        if( is::existset($this->attributes, 'accept') ){
            $accepted = array_filter(array_map('trim', explode(',', $this->attributes['accept'])), 'strlen');
            $valid = false;
            foreach($accepted as $mime){
                if( substr_count($mime, '/') != 1 || substr($mime, -1) == '/' || substr($mime, 0, 1) == '/' ) continue;
                if( substr($mime, -2) == '/*' ){
                    //generic accepted value...
                    if( strncmp($file['type'], substr($mime, 0, -2), strlen($mime) - 2) === 0 ){
                        $valid = true;
                        break;
                    }
                }else{
                    //specific value...
                    if( $file['type'] == $mime ){
                        $valid = true;
                        break;
                    }
                }
            }
            if( !$valid )
                return sprintf('The uploaded file(%s) is not of an excepted file type.', $file['name']);
        }
        return true;
    }
}