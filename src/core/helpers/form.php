<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * formHelper
 * Contains functions that help with form generation and validation.
 * 
 * @package    Munla
 * @subpackage core\helpers
 * @author     Chris Kolkman
 * @version    1.0
 */
class formHelper extends extender{
    
    /**
     * Stores the actual path to the folder that contains the form elements definitions.
     */
    public static $feFolder = null;
    
    /**
     * Some details about the open form.  Used to identify the open form,
     * as well as determine if there is an open form or not.
     */
    public $openForm = null;
    
    /**
     * Holds the next index/count of the form.  Static so that
     * multiple instances of formHelper still keep the correct form
     * count for the page.  Used to create names and ids when the user
     * fails to include them.
     */
    private static $nextFormIndex = -1;
    
    /**
     * Allows the user to turn automatic echoing off.
     */
    private $echoOff = false;
    
    /**
     * How many open <fieldset> tags there are.
     */
    private $openFieldSets = 0;
    
    /**
     * Whether the $_POST/$_GET values have been corrected for automatic character escaping.
     */
    protected static $postGetFixed = false;
    
    /**
     * Holds messages and other form data about the processed form.
     */
    public static $processed = null;
    
    /**
     * formHelper constructor.
     * 
     * @param bool $echoOff (optional) FALSE, form tags will automatically echo. TRUE form tags will be returned.
     * 
     * @return void
     */
    public function __construct($echoOff = false){
        $this->echoOff = is_bool($echoOff) ? $echoOff : isset($echoOff);
        $this->fixArrays();
    }
    
    /**
     * Autoload includes class files as they are needed.
     * 
     * @param string $name The name of the class to load.
     */
    public static function autoload($name){
        if( substr($name, 0, 3) == 'fe_' && isset(formHelper::$feFolder) )
            require (formHelper::$feFolder.substr($name, 3).'.php');
    }
    
    /**
     * Allows the user to turn on/off automatic echoing of form tags.
     * 
     * @param bool $value FALSE, form tags will automatically echo. TRUE form tags will be returned.
     * 
     * @return void
     */
    public function setEchoOff($value){
        $this->echoOff = is_bool($value) ? $value : isset($value);
    }
    
    /**
     * Gets the success message for the open form.
     * 
     * @return bool|array Returns an array of messages, or FALSE if no messages are found.
     */
    public function getMessage(){
        if( !isset($this->openForm) ) return false;
        if( !is_bool($this->openForm) && !is_array($this->openForm) )
            return self::getFormMessage($this->openForm);
        if( is_array($this->openForm) )
            return self::getFormMessage($this->openForm['formid']);
        return false;
    }
    
    /**
     * Gets the success message for the given form id.
     * 
     * @param string $formid The ID for the given for to get the message for.
     * 
     * @return bool|array Returns an array of messages, or FALSE if no messages are found.
     */
    public static function getFormMessage($formid){
        if( !isset(self::$processed) || !isset(self::$processed['form']) || !isset(self::$processed['form']['formid']) || !isset(self::$processed['msg']) || (count(self::$processed['msg']) < 1) )
            return false;
        
        if( !isset($formid) || self::$processed['form']['formid'] != $formid ) return false;
        return self::$processed['msg'];
    }
    
    /**
     * Gets the errors for the open form.
     * 
     * @return bool|array Returns an array of errors, or FALSE if no errors are detected.
     */
    public function getErrors(){
        if( !isset($this->openForm) ) return false;
        if( !is_bool($this->openForm) && !is_array($this->openForm) )
            return self::getFormErrors($this->openForm);
        if( is_array($this->openForm) )
            return self::getFormErrors($this->openForm['formid']);
        return false;
    }
    
    /**
     * Gets the errors for the given form id.
     * 
     * @param string $formid The ID for the given form to get errors for.
     * 
     * @return bool|array Returns an array of errors, or FALSE if no errors are detected.
     */
    public static function getFormErrors($formid){
        if( !isset(self::$processed) || !isset(self::$processed['form']) || !isset(self::$processed['form']['formid']) || !isset(self::$processed['errors']) || (count(self::$processed['errors']) < 1) )
            return false;
        
        if( !isset($formid) || self::$processed['form']['formid'] != $formid ) return false;
        return self::$processed['errors'];
    }
    
    /**
     * Gets whether this field had an error during form processing.
     * 
     * @return bool True if the field has an error, false otherwise.
     */
    public function hasFieldError($fieldid){
        if( !isset(self::$processed) || !isset(self::$processed['form']) || !isset(self::$processed['form']['formid']) || !isset(self::$processed['fielderrors']) || (count(self::$processed['fielderrors']) < 1) )
            return false;
        if( !isset($this->openForm) || (!is_string($this->openForm) && !is_array($this->openForm)) ) return false;
        $formid = is_string($this->openForm) ? $this->openForm : $this->openForm['formid'];
        if( self::$processed['form']['formid'] != $formid ) return false;
        return in_array($fieldid, self::$processed['fielderrors']);
    }
    
    /**
     * Opens a new form tag.
     * 
     * Has variable parameters.  May either pass an associative array with the parameters and HTML attributes mixed together
     * or the paramters may be passed in order, with an additional array parameter containing the HTML attributes.
     * 
     * @param callable $callback The callback function for handling the form.
     * @param bool $nocsrf       Indicator to use CSRF or not.
     * @param string $class      The HTML class attribute.
     * @param string $method     The HTML method attribute.
     * @param string $action     The HTML action attribute.
     * @param array $attributes  Other HTML attributes for the tag.
     * 
     * @return string|bool
     */
    public function open($arg){
        if( isset($this->openForm) ){
            log::warning('Unable to open a new form - a form is already open.');
            return false;
        }
        
        $args = func_get_args();
        if( count($args) == 1 && is_array($arg) && !is_callable(get::form_callback($arg)) ) $args = $arg;
        if( count($args) > 0 ){
            $n = array('callback' => null, 'nocsrf' => false, 'class' => null, 'method' => null, 'action' => null);
            $attributes = null;
            if( is_array(end($args)) ){
                $eargs = array_pop($args);
                //could be params or only as callback
                if( is_callable(get::form_callback($eargs)) ) $n['callback'] = get::form_callback($eargs);
                else $attributes = $eargs;
            }
            $did = 0; //$dargs = array('callback', 'class', 'method', 'action', 'nocsrf');
            while(count($args) > 0){
                $a = array_shift($args);
                if( $did < 2 && (is_string($a) || is_array($a)) && is_callable(get::form_callback($a)) ){ $n['callback'] = get::form_callback($a); $did++; continue; }
                if( $did < 2 && is_bool($a) ){ $n['nocsrf'] = $a; $did++; continue; }
                if( is_string($a) ){
                    if( $did < 3 ){ $n['class'] = $a; $did = 3; continue; }
                    if( $did < 4 ){ $n['method'] = $a; $did = 4; continue; }
                    if( $did < 5 ){ $n['action'] = $a; $did = 5; continue; }
                }
            }
            if( is_array($attributes) ){
                foreach($attributes as $ak => $av)
                    $n[$ak] = $av;
            }
            $args = $n;
        }
        
        set::array_default($args, 'method', 'post', array('get', 'post', 'GET', 'POST'));
        set::array_default($args, 'action', get::url());
        set::array_default($args, 'nocsrf', false, array(true, false));
        
        $formid = get::array_default($args, 'id', 'form_'.(++self::$nextFormIndex), array(), true);
        $callback = get::array_default($args, 'callback');
        
        if( !isset($callback) || !is_callable($callback) )
            log::warning('Form callback is missing or invalid.  The form callback should be a static member of a class that extends formHandler.');
        
        $html = '';
        $args = array_filter($args, function($v){ return !is_null($v); });
        if( is::existset($args, 'nocsrf') && $args['nocsrf'] ){
            $args['nocsrf'] = 'nocsrf';
            if( isset($callback) && is_callable($callback) ){
                $mnlAction = self::generateAction($callback);
                $html = sprintf('<form%s><input%s />', get::formattedAttributes($args), get::formattedAttributes(array('type' => 'hidden', 'name' => 'mnl_formaction', 'value' => $mnlAction)));
                $this->openForm = $callback;
            }else{
                $html = sprintf('<form%s>', get::formattedAttributes($args));
                $this->openForm = true;
            }
        }else{
            $csrfName = csrfHelper::generateName();
            if( !is::existset(munla::$session, 'forms') ) munla::$session['forms'] = array();
            if( !is::existset(munla::$session['forms'], $csrfName) ) munla::$session['forms'][$csrfName] = array();
            munla::$session['forms'][$csrfName][$formid] = array();
            $this->openForm = &munla::$session['forms'][$csrfName][$formid];
            
            $this->openForm['formid'] = $formid;
            $this->openForm['callback'] = $callback;
            $this->openForm['method'] = $args['method'];
            $this->openForm['fields'] = array();
            $this->openForm['nocsrf'] = $args['nocsrf'];
            
            unset($args['nocsrf']);
            $html = sprintf('<form%s><input%s />', get::formattedAttributes($args), get::formattedAttributes(array('type' => 'hidden', 'name' => 'mnl_formid', 'value' => $csrfName.'-'.$formid)));
        }
        
        formElement::$fieldNameArrays = array();
        
        if( !$this->echoOff ) echo $html;
        return $html;
    }
    
    /**
     * Generates the action parameter for simple forms.
     * 
     * @param callable $callback The callback function for handling the form.
     * 
     * @return string
     */
    private static function generateAction($callback){
        $token = uniqid(md5(mt_rand()), true);
        $hash = sha1(config::$csrf_form_secret.'-'.$callback.'-'.$token);
        return $callback.'-'.$token.'-'.$hash;
    }
    
    
    /**
     * Validates a given form action.
     * 
     * Returns a string containing the callback on success, or a boolean false on failure.
     * 
     * @param string $token The token generated by the generateAction method.
     * 
     * @return string|bool
     */
    private static function validateAction($token){
        if( !isset($token) ) return false;
        //break down the csrf token into parts
        $parts = explode('-', $token);
        if( count($parts) != 3 ) return false;
        list($callback, $token, $hash) = $parts;
        
        $result = (sha1(config::$csrf_form_secret.'-'.$callback.'-'.$token) == $hash);
        if( $result ) $result = $callback;
        return $result;
    }
    
    /**
     * Removes all defined forms.
     * 
     * @return void
     */
    public function clearForms(){
        if( is::existset(munla::$session, 'forms') )
            unset(munla::$session['forms']);
    }
    
    /**
     * Closes an open form.
     * 
     * @return string|bool
     */
    public function close($closeWholeForm = true){
        if( !isset($this->openForm) ){
            log::warning('Unable to close form - there is no open form to close.');
            return false;
        }
        
        $html = '</form>';
        unset($this->openForm);
        
        if( !$this->echoOff ) echo $html;
        return $html;
    }
    
    /**
     * Gets the form to process, or given the form to process - processes it.
     * 
     * @param string|null $form
     *   The form identifier of the form to process.
     * 
     * @return string|bool|null 
     *   When $form is null, returns either null or a string form identifier.
     *   When a form identifier is passed, returns a boolean indicating the success or failure of the form processing.
     */
    public static function process($form = null){
        if( isset(self::$processed) ){
            log::warning('Invalid call to formHelper::process() - the form has already been processed.');
            return false;
        }
        
        if( !isset($form) ){
            if( is::existset(munla::$session, 'forms') ){
                foreach(munla::$session['forms'] as $csrfName => $csrfForms){
                    foreach($csrfForms as $formid => $f){
                        if( !is::existset($f, 'callback') || !is_callable($f['callback']) )
                            continue;
                        
                        $_FORM = array();
                        switch(strtolower($f['method'])){
                            case 'post': $_FORM = &$_POST; break;
                            case 'get': $_FORM = &$_GET; break;
                        }
                        
                        if( !is::existset($_FORM, 'mnl_formid') ){
                            unset($_FORM);
                            continue;
                        }
                        
                        if( substr($_FORM['mnl_formid'], 0, strlen($csrfName)+1) == $csrfName.'-' && substr($_FORM['mnl_formid'], strlen($csrfName)+1) == $formid ){
                            unset($_FORM);
                            return sprintf('%s::%s', $csrfName, $formid);
                        }
                        unset($_FORM);
                    }
                }
            }
            
            $mnl_formaction = self::validateAction((
                is::existset($_POST, 'mnl_formaction') ? 
                    $_POST['mnl_formaction'] : 
                    (is::existset($_GET, 'mnl_formaction') ?
                        $_GET['mnl_formaction'] :
                        null)));
            
            if( $mnl_formaction )
                return 'simpleForm::'.$mnl_formaction;
            return $form;
        }
        
        if( !is_string($form) || strpos($form, '::') === false ){
            log::error(sprintf('Invalid form identifier "%s"', $form));
            return false;
        }
        list($csrfName, $formid) = explode('::', $form, 2);
        if( $csrfName == 'simpleForm' && is_callable($formid) ){
            //$formid(); //trigger the callback - we don't know the values or form definition so no parameters
            $_FORM = array();
            if( is::existset($_POST, 'mnl_formaction') ) $_FORM = &$_POST;
            if( is::existset($_GET, 'mnl_formaction') ) $_FORM = &$_GET;
            self::fixArrays();
            unset($_FORM['mnl_formaction']);
            
            //normalize the file listing into a better array if any files were uploaded
            self::fixFileArray($_FORM);
            
            self::$processed = array('errors' => array(), 'fielderrors' => array(), 'msg' => null);
            self::$processed['form']['formid'] = $formid;
            $p = get::helper('form');
            $processed = call_user_func($formid, $p, $_FORM);
            if( $processed === false ) self::$processed['errors'][] = 'Failed to process the form.';
            elseif( $processed !== true ){
                if( is_array($processed) ){
                    foreach($processed as $err){
                        $success = false;
                        switch(substr($err, 0, 1)){
                            case '+': $err = substr($err, 1); $success = true; break;
                            case '-': $err = substr($err, 1); break;
                        }
                        self::$processed[($success ? 'msg' : 'errors')][] = $err;
                    }
                }else{
                    $success = false;
                    switch(substr($processed, 0, 1)){
                        case '+': $processed = substr($processed, 1); $success = true; break;
                        case '-': $processed = substr($processed, 1); break;
                    }
                    self::$processed[($success ? 'msg' : 'errors')][] = $processed;
                }
            }
            return (count(self::$processed['errors']) < 1);
        }
        if( !is::existset(munla::$session, 'forms') || !is::existset(munla::$session['forms'], $csrfName) || !is::existset(munla::$session['forms'][$csrfName], $formid) ){
            log::error(sprintf('Specified form definition "%s" was not found', $form));
            return false;
        }
        
        $form = munla::$session['forms'][$csrfName][$formid];
        if( !is::existset($form, 'callback') || !is_callable($form['callback']) ){
            log::error(sprintf('Form does not have a valid callback.'));
            return false;
        }
        $callback = explode('::', $form['callback']);
        
        $_FORM = array();
        switch(strtolower($form['method'])){
            case 'post': $_FORM = &$_POST; break;
            case 'get': $_FORM = &$_GET; break;
        }
        
        self::fixArrays();
        
        self::$processed = array('errors' => array(), 'fielderrors' => array(), 'msg' => null);
        self::$processed['form'] = $form;
        
        if( is::existset($_FORM, 'mnl_formid') ) unset($_FORM['mnl_formid']);
        
        //normalize the file listing into a better array if any files were uploaded
        self::fixFileArray($_FORM);
        
        //fix up special field types
        foreach($form['fields'] as $field){
            $type = get_class($field);
            if( $type == 'fe_image' ){
                $name = $field->getName();
                if( !is::existset($_FORM, $name) && is::existset($_FORM, $name.'_x') )
                    $_FORM[$name] = true;
            }elseif( $type == 'fe_session' )
                $_FORM[$field->getName()] = $field->get_value();
        }
        
        $fields = new formFieldList($form['fields']);
        $validating = array($callback[0], $callback[1].'_validating');
        $validate = array($callback[0], $callback[1].'_validate');
        if( is_callable($validating) ) $validating($this, $fields, $_FORM);
        
        $valid = is_callable($validate) ? $validate($_FORM) : self::validate($fields, $_FORM, (strtolower($form['method']) == 'post' && !$form['nocsrf']));
        if( $valid ){
            $processed = $callback($_FORM);
            
            if( isset($processed) ){
                if( $processed === false ) self::$processed['errors'][] = 'Failed to process the form.';
                elseif( $processed !== true ){
                    if( is_array($processed) ){
                        foreach($processed as $err){
                            $success = false;
                            switch(substr($err, 0, 1)){
                                case '+': $err = substr($err, 1); $success = true; break;
                                case '-': $err = substr($err, 1); break;
                            }
                            self::$processed[($success ? 'msg' : 'errors')][] = $err;
                        }
                    }else{
                        $success = false;
                        switch(substr($processed, 0, 1)){
                            case '+': $processed = substr($processed, 1); $success = true; break;
                            case '-': $processed = substr($processed, 1); break;
                        }
                        self::$processed[($success ? 'msg' : 'errors')][] = $processed;
                    }
                }
            }
        }elseif( count(self::$processed['errors']) < 1 ){
            self::$processed['errors'][] = 'Failed form validation.';
        }
        return (count(self::$processed['errors']) < 1);
    }
    
    
    
    /**
     * INPUT TYPES AND TAGS START HERE
     */
    
    
    /**
     * Creates a label.
     * 
     * Minimum of 2 parameters. $text and $element, or $text and $for.
     * When using $text and $element, the can appear in either order and the order determines
     * where the label is in relation to the input element.  When using
     * $text and $for, they must be in that order.
     * 
     * @param string $text         The text for the label.
     * @param formElement $element The element to provide the label for.
     * @param string $for          The ID of the element it is for.
     * @param bool $insideTag      Whether to place the formElement inside the label tag or outside of it.
     * @param string $class        The class to give the label.
     * @param array $attributes    The other attributes to give the label.
     * 
     * @return string|void
     */
    public function ov_label($text, $for, $insideTag = null, $class = null){
        $args = func_get_args();
        if( count($args) > 5 || count($args) < 2 ) throw new InvalidArgumentException('Invalid number of arguments passed to label().');
        
        $e = end($args);
        if( is_array($e) ) array_pop($args);
        else unset($e);
        reset($args);
        
        $label = '';
        $params = array(); $outElement = 0;
        if( (is_string($text) && is_object($for) && is_subclass_of($for, 'formElement')) || (is_string($for) && is_object($text) && is_subclass_of($text, 'formElement')) ){
            //based off of formElement
            if( is_string($for) ){ $outElement = -1; $t = $for; $for = $text; $text = $t; }
            else{ $outElement = 1; }
            $label = $text;
            $params['for'] = $for->getId();
        }elseif( is_string($text) && is_string($for) ){
            $label = $text;
            $params['for'] = $for;
        }else throw new InvalidArgumentException('Invalid argument detected - may only submit strings or formElements.');
        if( isset($insideTag) && is_string($insideTag) ){ $class = $insideTag; $insideTag = false; }
        if( isset($class) && is_string($class) ) $params['class'] = $class;// $class = sprintf(' class="%s"', get::entities($class));
        if( isset($e) && is_array($e) ) $params = $params + $e;
        
        if( !array_key_exists('onclick', $params) ) $params['onclick'] = ' ';
        
        $html = '';
        if( $outElement != 0 ){
            if( $insideTag )
                $lbl = ($outElement < 0) ? '<label%1$s>%3$s%2$s</label>' : '<label%s>%s%s</label>';
            else
                $lbl = ($outElement < 0) ? '%3$s<label%1$s>%2$s</label>' : '<label%s>%s</label>%s';
            $html = sprintf($lbl, get::formattedAttributes($params), $label, $for);
        }else
            $html = sprintf('<label%s>%s</label>', get::formattedAttributes($params), $label);
        
        if( !$this->echoOff ) echo $html;
        return $html;
    }
    
    /**
     * Generates a special hidden value that won't be output to the browser,
     * but will still be included like a hidden form element (uses sessions).
     * 
     * This won't work though if a form hasn't been opened or won't be processed through
     * this form helper.
     * 
     * @param string $name  The name of the hidden value.
     * @param string $value The value.
     * 
     * @return void
     */
    public function session($name, $value){
        if( isset($this->openForm) && is_array($this->openForm) )
            $this->openForm['fields'][] = new fe_session(array('name' => $name, 'value' => $value));
    }
    
    /**
     * Generates a hidden form field - validation should be custom.
     * 
     * Not stored with field definitions.
     * 
     * @param string $name  The name of the hidden value.
     * @param string $value The value.
     * @param string $id    (optional) The ID attribute.
     * 
     * @return fe_hidden
     */
    public function ov_hidden($name, $value = '', $id = null){
        $hidden = new fe_hidden(array('name' => $name, 'value' => $value));
        $hidden->setId($id);
        
        if( isset($this->openForm) && is_array($this->openForm) )
            $this->openForm['fields'][] = $hidden;
        
        if( !$this->echoOff ) echo $hidden;
        return $hidden;
    }
    
    /**
     * Generates a datalist.
     * 
     * @param string $id    (optional) The id for the datalist.
     * @param array $values The values for the datalist. This should NOT be associative to avoid parameter detection in other input field types.
     * 
     * @return fe_datalist
     */
    public function ov_datalist($id){
        $args = func_get_args();
        if( count($args) > 2 ) throw new InvalidArgumentException('Too many arguments passed to datalist().');
        if( count($args) == 1 && !is_array($id) )
            throw new InvalidArgumentException('For one argument: expecting an array.');
        if( count($args) == 2 && (!is_string($id) || !is_array($args[1])) )
            throw new InvalidArgumentException('For two arguments: expeting a string, then an array.');
        
        if( count($args) == 1 ){
            $values = $id;
            $id = null;
        }else $values = $args[1];
        
        $datalist = new fe_datalist($values);
        $datalist->setId($id);
        
        if( !$this->echoOff ) echo $datalist;
        return $datalist;
    }
    
    /**
     * Generates a text field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_text($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('text', $name, $value, true, $args);
    }
    
    /**
     * Generates a search field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_search($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('search', $name, $value, true, $args);
    }
    
    /**
     * Generates a telephone field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * There are some custom attributes that may be used to change how the field is validated:
     *  - validatemode: string, Possible values are: 'none', 'us'. Determines the validation mode of the phone number. By default according to HTML specifications no validation is done. When set to "us", the phone number is validated as a US phone number (no country code).
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_tel($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('tel', $name, $value, true, $args);
    }
    
    /**
     * Generates a url field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_url($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('url', $name, $value, true, $args);
    }
    
    /**
     * Generates a email field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_email($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('email', $name, $value, true, $args);
    }
    
    /**
     * Generates a password field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_password($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('password', $name, $value, false, $args);
    }
    
    /**
     * Generates a datetime-local field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_datetimelocal($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('datetimelocal', $name, $value, true, $args);
    }
    
    /**
     * Generates a datetime field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_datetime($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('datetime', $name, $value, true, $args);
    }
    
    /**
     * Generates a date field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_date($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('date', $name, $value, true, $args);
    }
    
    /**
     * Generates a month field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_month($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('month', $name, $value, true, $args);
    }
    
    /**
     * Generates a week field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_week($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('week', $name, $value, true, $args);
    }
    
    /**
     * Generates a time field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_time($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('time', $name, $value, true, $args);
    }
    
    /**
     * Generates a number field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * There are some custom attributes that may be used to change how the number is validated:
     *  - integeronly: bool, Requires that the number submitted must only contain integers. Default is false.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_number($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('number', $name, $value, true, $args);
    }
    
    /**
     * Generates a range field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_range($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('range', $name, $value, true, $args);
    }
    
    /**
     * Generates a color field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $data,... OPTIONAL A number of additional pieces of data to apply to this form element.
     *   - Any number of associative arrays containing html attributes as the keys.
     *   - ONE array containing non-attributes as the keys to be used as the datalist.
     *   If more than one non-attribute array is supplied, only the LAST one will be used as the datalist.
     * 
     * @return formElement|void
     */
    public function ov_color($name, $value = null){
        $args = func_get_args(); array_shift($args); array_shift($args);
        if( !isset($value) || strlen(trim($value)) < 1 ) $value = '#000000';
        return $this->generate_input('color', $name, $value, true, $args);
    }
    
    /**
     * Generates a checkbox field.
     * 
     * Has varied parameters. First three must be $name, $checked, and $value. After the first three, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param bool $checked     Whether the checkbox should be checked or not.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_checkbox($name, $checked = false, $value = null){
        $args = func_get_args(); array_shift($args); array_shift($args); array_shift($args);
        $off = $this->echoOff; $this->echoOff = true;
        $chk = $this->generate_input('checkbox', $name, $value, false, $args);
        $chk->setCheckedState($checked);
        $this->echoOff = $off;
        if( !$this->echoOff ) echo $chk;
        return $chk;
    }
    
    /**
     * Generates a radio button field.
     * 
     * Has varied parameters. First three must be $name, $value, and $checked. After the first three, the order
     * does not matter as long as they are a string or an array.
     * 
     * There are some custom attributes that may be used to change how the radio group is validated:
     *  - allowchange: bool, Allows the radio group to change. By default if you do not select an element that was in the group when the element was created it will be evaluated as invalid input. Setting allowchange to true allows JavaScript to make changes to the group, and it won't be considered invalid input.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param bool $checked     Whether the radio button should be checked or not.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_radio($name, $value, $checked = false){
        $args = func_get_args(); array_shift($args); array_shift($args); array_shift($args);
        $off = $this->echoOff; $this->echoOff = true;
        $rdo = $this->generate_input('radio', $name, $value, false, $args);
        $rdo->setCheckedState($checked);
        $this->echoOff = $off;
        if( !$this->echoOff ) echo $rdo;
        return $rdo;
    }
    
    /**
     * Generates a file field.
     * 
     * Has varied parameters. The first $name. After that, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_file($name){
        $args = func_get_args(); array_shift($args);
        return $this->generate_input('file', $name, null, false, $args);
    }
    
    /**
     * Generates an image submit button.
     * 
     * Has varied parameters. The first two are required, though $value depends on what attributes are passed.
     * If no "src" attribute is passed in any of the $attributes arrays, then $value should be the url to the
     * source image (probably the most common usage).  If the source _is_ passed through an attribute array
     * then $value will show up as an attribute itself.
     * 
     * @param string $name  The name of the field.
     * @param string $value SRC url, or if one is specified in an attribute array - the value.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_image($name, $value){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('image', $name, $value, false, $args);
    }
    
    /**
     * Generates a submit button.
     * 
     * Generates a <button> of type submit, rather than the input type.  This allows for greater control over
     * the appearance of the submit button, including putting images into the button.  Take care some browsers
     * handle what is submitted differently (<IE8 submitted content rather than value, possibly other issues as well).
     * 
     * Has varied parameters. The first three must be $name, $content, and $value - though only $name is required.
     * 
     * @param string $name The name of the button
     * @param string $content What to display on the button - this may include HTML.
     * @param string $value The value to submit to the server.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_submit($name, $content = 'Submit', $value = 'Submit'){
        $args = func_get_args(); array_shift($args); array_shift($args); array_shift($args);
        $args[] = array('content' => $content);
        return $this->generate_input('submit', $name, $value, false, $args);
    }
    
    /**
     * Generates a reset button.
     * 
     * Generates a <button> of type reset, rather than the input type.  This allows for greater control over
     * the appearance of the submit button, including putting images into the button.
     * 
     * Has varied parameters. The first two must be $name and $content - though only $name is required.
     * 
     * @param string $name The name of the button
     * @param string $content What to display on the button - this may include HTML.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_reset($name, $content = 'Reset'){
        $args = func_get_args(); array_shift($args); array_shift($args);
        $args[] = array('content' => $content);
        return $this->generate_input('reset', $name, null, false, $args);
    }
    
    /**
     * Generates a button.
     * 
     * Has varied parameters.
     * 
     * @param string $content What to display on the button - this may include HTML.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_button($content){
        $args = func_get_args(); array_shift($args);
        $args[] = array('content' => $content);
        return $this->generate_input('button', '', null, false, $args);
    }
    
    /**
     * Generates a select list.
     * 
     * Has varied parameters. The first three must be $name, $list, and $value - though only the first two are required.
     * 
     * There are some custom attributes that may be used to change how the select list is build/validated:
     *  - keyvalues: bool, Allows numeric indicies to be useds as option values. Default is false.
     *  - placeholder: string, Creates a placeholder element as the first element.  When submitted with this element selected it will evaluate to empty. Default is no placeholder.
     *  - separator: bool|string, Inserts a separator between the placeholder and the rest of the list. If a string it also defines what the separator should be. Default is no separator, with a defined separator of 15 consecutive dashes.
     *  - allowchange: bool, Allows the list to change. By default if you do not select an element that was in the supplied list when the element was created it will be evaluated as invalid input. Setting allowchange to true allows JavaScript to make changes to the list, and it won't be considered invalid input.
     * 
     * @param string $name      The name of the field.
     * @param array $list       The list of options for the select list. Multi-dimensional arrays can be used to specify optgroups.
     *   By default only associative keys will be used as option values, except when used as the key for a subarray - in which case it will be the label.
     *   When a value is a single dash (-) a separator item is assumed and the defined separator is used instead.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_select($name, array $list, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args); array_shift($args);
        $dlist = array();
        $p = $this->get_element_attributes('select', $name, $value, $dlist, $args);
        
        $element = new fe_select($p);
        $element->setList($list);
        if( isset($this->openForm) && is_array($this->openForm) ) $this->openForm['fields'][] = $element;
        
        if( !$this->echoOff ) echo $element;
        return $element;
    }
    
    /**
     * Generates a textarea field.
     * 
     * Has varied parameters. First two must be $name and $value. After the first two, the order
     * does not matter as long as they are a string or an array.
     * 
     * @param string $name      The name of the field.
     * @param string $value     The value for the field.
     * @param string $literal,... OPTIONAL A number of optional string literals that correspond to attributes.
     *   - The name of the boolean attribute to apply OR
     *   - The name of the class to apply.
     *   If more than one non-attribute is supplied, only the LAST one will be used as the class.
     * @param array $attributes,... OPTIONAL Any number of associative arrays containing html attributes as the keys.
     * 
     * @return formElement|void
     */
    public function ov_textarea($name, $value = ''){
        $args = func_get_args(); array_shift($args); array_shift($args);
        return $this->generate_input('textarea', $name, $value, false, $args);
    }
    
    /**
     * Checks whether the given array has keys that are in the second array.
     * 
     * @param array $arr The array to check the keys of.
     * @param array $html The list of keys to check for.
     * 
     * @return bool
     */
    private function hasHtmlAttributes(array &$arr, array &$html){
        return (count(array_intersect(array_keys($arr), $html)) > 0);
    }
    
    /**
     * Gets an attribute array for the given form element.
     * 
     * @param string $type The type of form element.
     * @param string $name The name of the form element.
     * @param string $value The value of the form element.
     * @param array $datalist A reference to the datalist variable to pass the gathered datalist back to.
     * @param array $args The list of arguements passed to the original function.  This will be scanned for attributes.
     * @param array $required OPTIONAL A list of required attributes, that will throw an exception if any are missing.
     * 
     * @return array
     */
    private function get_element_attributes($type, $name, $value, &$datalist, array $args, array $required = array()){
        $p = array('name' => $name, 'value' => $value);
        
        $etype = 'fe_'.$type;
        $allowed = $etype::acceptedAttributes();
        $datalist = array();
        while(count($args) > 0){
            $arg = array_shift($args);
            if( is_string($arg) ){
                $larg = strtolower($arg);
                if( !array_key_exists($larg, $p) || !array_key_exists('class', $p) ){
                    if( in_array($larg, formElement::$boolAttributes) )
                        $p[$larg] = $larg;
                    elseif( array_key_exists($larg, formElement::$enumAttributes) && array_key_exists(true, formElement::$enumAttributes[$larg]) )
                        $p[$larg] = formElement::$enumAttributes[$larg][true];
                    else
                        $p['class'] = $arg;
                }
            }
            if( is_array($arg) && count($arg) > 0 ){
                if( $this->hasHtmlAttributes($arg, $allowed) ){
                    foreach($arg as $n => $v){
                        $n = strtolower($n);
                        if( !array_key_exists($n, $p) ){
                            if( array_key_exists($n, formElement::$enumAttributes) && !in_array($v, formElement::$enumAttributes[$n]) && array_key_exists($v, formElement::$enumAttributes[$n]) )
                                $v = formElement::$enumAttributes[$n][$v];
                            elseif( in_array($n, formElement::$boolAttributes) ){
                                if( (is_bool($v) && !$v) || (!is_bool($v) && $v !== 'true') ) continue;
                                $v = $n;
                            }
                            $p[$n] = $v;
                        }
                    }
                }elseif( in_array('list', $allowed) )
                    $datalist = $arg;
            }
        }
        
        foreach($required as $n){
            if( !array_key_exists($n, $p) )
                throw new InvalidArgumentException(sprintf('Missing required attribute "%s".', $n));
        }
        
        return $p;
    }
    /**
     * Shared functionality between most of the form elements. Actually creates the form element and either outputs it or returns it.
     * 
     * @param string $type        Input type attribute
     * @param string $name        Input name attribute
     * @param string $value       Input value attribute
     * @param bool $allowDataList Whether a found datalist should be allowed or not.
     * @param array $args         Other function arguments.
     * @param array $required     List of attributes that MUST be supplied to the element.
     * 
     * @return string|void
     */
    private function generate_input($type, $name, $value, $allowDataList, array $args, array $required = array()){
        $datalist = array();
        $p = $this->get_element_attributes($type, $name, $value, $datalist, $args, $required);
        
        $etype = 'fe_'.$type;
        $element = new $etype($p);
        
        if( $allowDataList === true && count($datalist) > 0 ) $element->setDatalist(new fe_datalist($datalist));
        if( isset($this->openForm) && is_array($this->openForm) ) $this->openForm['fields'][] = $element;
        
        if( !$this->echoOff ) echo $element;
        return $element;
    }
    
    /**
     * Validates the submitted form values according to the field definitions.
     * 
     * @param array $fields An array containing the field definitions.
     * @param array $values An array containing the submitted values.
     * @param bool $csrf Whether to validate CSRF or not.
     * 
     * @return bool Returns TRUE upon successfull validation, FALSE otherwise.
     */
    private static function validate(formFieldList $fields, array &$values, $csrf){
        $errors = array();
        $fieldErrors = array();
        if( $csrf ){
            try{
                $v = csrfHelper::validate_form();
                if( $v === false ) $errors[] = 'Form failed CSRF validation.';
                unset($values['csrf_token']);
            }catch(Exception $e){
                $errors[] = $e->getMessage();
            }
        }
        
        if( count($errors) < 1){
            foreach($fields as $name => $field){
                if( in_array(get_class($field), array('fe_session', 'fe_submit', 'fe_button', 'fe_reset', 'fe_image')) )
                    continue;
                
                $value = &$field->getValue($values);
                if( $field instanceof fe_validator ){
                    if( $field instanceof fe_radio ) $validated = $field->validate($value, $fields);
                    else $validated = $field->validate($value);
                    
                    if( !isset($validated) || $validated !== true ){
                        $msg = !isset($validated) ? 'Field "%s" did not get validated.' : ($validated === false ? 'Validation failed on field "%s".' : $validated);
                        $errors[] = sprintf($msg, $field->getName()); //$field['msglbl']);
                        $fieldErrors[] = $field->getId();
                    }
                }
            }
        }
        
        foreach($errors as $error) self::$processed['errors'][] = $error;
        foreach($fieldErrors as $fe) self::$processed['fielderrors'][] = $fe;
        return (count($errors) < 1);
    }
    
    
    /**
     * SELF-HELP ROUTINES START HERE.
     */
    
    
    /**
     * Safely fixes automatic character escaping in the given array ONCE.
     * 
     * @return void
     */
    public static function fixArrays(){
        if( self::$postGetFixed ) return;
        if( count($_POST) > 0 )
            self::doFixArray($_POST);
        if( count($_GET) > 0 )
            self::doFixArray($_GET);
        self::$postGetFixed = true;
    }
    
    /**
     * Fixes automatic character escaping in the given array.
     * 
     * @param array $array
     *   An array containing the values to be checked for automatic escaping.
     * 
     * @return void
     */
    private static function doFixArray(array &$array){
        if( get_magic_quotes_gpc() == 1 ){
            foreach($array as $key => $value){
                if( is_array($value) )
                    $array[$key] = self::doFixArray($value);
                else
                    $array[$key] = stripslashes($value);
            }
        }
    }
    
    /**
     * Adds the file array to the form POST/GET array and normalizes
     * it rather then keeping the weird _FILES structure.
     * 
     * Called only once a form has been identified for processing.
     * 
     * @param array $arr
     *   The array to add the file information to, usually a reference to
     *   the _POST or _GET superglobal array.
     * 
     * @return void
     */
    private static function fixFileArray(array &$arr){
        $fileKeys = array('name', 'type', 'tmp_name', 'error', 'size');
        foreach($_FILES as $basename => $fileArr){
            $res = array();
            foreach($fileKeys as $key){
                if( array_key_exists($key, $fileArr) ){
                    if( is_array($fileArr[$key]) ){
                        $subkey = self::flatten_file($fileArr[$key]);
                        foreach($subkey as $k => $v){
                            $res[$basename.$k.'['.$key.']'] = ($key == 'type' ? strtolower($v) : $v);
                            if( $key == 'name' && !array_key_exists($basename.$k.'[ext]', $res) )
                                $res[$basename.$k.'[ext]'] = get::fullext($v);
                        }
                    }else{
                        $res[$basename.'['.$key.']'] = ($key == 'type' ? strtolower($fileArr[$key]) : $fileArr[$key]);
                        if( $key == 'name' && !array_key_exists($basename.'[ext]', $res) )
                            $res[$basename.'[ext]'] = get::fullext($fileArr[$key]);
                    }
                }
            }
            foreach($res as $path => $v){
                $pa = formElement::getFieldPath($path, false, false);
                $ref = &$arr;
                while( count($pa) > 0 ){
                    $idx = array_shift($pa);
                    if( !array_key_exists($idx, $ref) )
                        $ref[$idx] = array();
                    $ref = &$ref[$idx];
                }
                $ref = $v;
                unset($ref);
            }
        }
    }
    
    /**
     * Flattens a given array to a multi-dimensional string representation of
     * the path as the key name, in a single dimensional array.
     * 
     * @param array $array
     *   The array to flatten.
     * 
     * @param array $new
     *   Only used for recursion. The array to add the flattened elements to.
     * 
     * @param string $prefix
     *   Only used for recursion. The key that will be used for the flattened element.
     *   Grows as the subarrays are processed.
     * 
     * @return array
     *   Returns a flattened array, with the keys as a string representation of the
     *   multi-dimensional keys it used to have.
     */
    private static function &flatten_file(array $array, array &$new = array(), $prefix = ''){
        foreach($array as $key => $value){
            if( is_array($value) ){
                $p = $prefix.'['.$key.']';
                $new = &self::flatten_file($value, $new, $p);
            }else{
                $new[$prefix.'['.$key.']'] = $value;
            }
        }
        return $new;
    }
    
}

/* Get the directory of THIS file so we can more easily specify sub directories */
$thisdir = strtr(dirname(__FILE__), '\\', '/');
if( substr($thisdir, -1) != '/' ) $thisdir .= '/';

formHelper::$feFolder = $thisdir.'form/';

require formHelper::$feFolder.'formElement.php';
spl_autoload_register(array('formHelper', 'autoload'), true, true);

class formFieldList implements ArrayAccess, Countable, IteratorAggregate{
    
    private $list = array();
    private $name_idx = array();
    
    public function __construct(array &$fields){
        foreach($fields as &$f){
            $name = $f->getName();
            if( array_key_exists($name, $this->name_idx) ){
                $nname = sprintf('%s[%s]', $name, ++$this->name_idx[$name]);
                $this->list[$nname] = &$f;
            }elseif( isset($this->list[$name]) ){
                $this->name_idx[$name] = -1;
                $oname = sprintf('%s[%s]', $name, ++$this->name_idx[$name]);
                $nname = sprintf('%s[%s]', $name, ++$this->name_idx[$name]);
                $this->list[$oname] = &$this->list[$name];
                $this->list[$nname] = &$f;
                unset($this->list[$name]);
            }else
                $this->list[$name] = &$f;
        }
    }
    
    public function count(){ return count($this->list); }
    public function offsetExists($offset){ return isset($this->list[$offset]); }
    public function &offsetGet($offset){
        $n = null;
        if( isset($this->list[$offset]) )
            $n = &$this->list[$offset];
        return  $n;
    }
    public function offsetSet($offset, $value){ $this->list[$offset] = $value; }
    public function offsetUnset($offset){ unset($this->list[$offset]); }
    public function getIterator(){ return new ArrayIterator($this->list); }
    
}