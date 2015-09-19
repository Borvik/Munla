<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * get
 * Contains functions that "get" information.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class get extends cache{
    
    /**
     * Encodes the given text as HTML entities.
     * 
     * @param string $text The text to encoded.
     * @param bool $double_encode (optional) Attempts to detect if the string already has HTML character entities, and skips if detected.
     * 
     * @return string The encoded text.
     */
    public static function entities($text, $double_encode = false){
        if( !is_string($text) || $text === null ) return $text;
        $flags = ENT_QUOTES | ENT_HTML401;
        $srcEncoding = mb_detect_encoding($text, mb_detect_order(), true);
        if( $srcEncoding === false || $srcEncoding == 'ASCII' ) $srcEncoding = 'UTF-8';
        return htmlentities($text, $flags, $srcEncoding, $double_encode);
    }
    
    /**
     * Escapes characters in the given string.
     * 
     * @param string $str The string containing characters to escape.
     * @param array $chars The characters that need escaping.
     * @param string $escapeChar The escape character to use.
     * 
     * @return string The escaped string.
     */
    public static function cache_escaped_str($str, array $chars, $escapeChar = '\\'){
        $indicies = array();
        foreach($chars as $c){
            $start = 0;
            while($i = strpos($str, $c, $start)){
                $start = $i + 1;
                if( array_key_exists($c, $indicies) && (in_array($i, $indicies[$c]) || end($indicies[$c]) + strlen($c) > $i) ) continue;
                $indicies[$c][] = $i;
            }
        }
        $escLen = strlen($escapeChar);
        $charactersAdded = 0;
        foreach($indicies as $c => $charIndicies){
            foreach($charIndicies as $i){
                $str = substr($str, 0, $i + $charactersAdded).$escapeChar.substr($str, $i + $charactersAdded);
                $charactersAdded += $escLen;
            }
        }
        return $str;
    }
    
    /**
     * Unescapes characters in the given string.
     * 
     * @param string $str The string containing characters to unescape.
     * @param array $chars The characters that need unescaping.
     * @param string $escapeChar The escape character that was used.
     * 
     * @return string The unescaped string.
     */
    public static function cache_unsecaped_str($str, array $chars, $escapeChar = '\\'){
        $indicies = array();
        foreach($chars as $c){
            $start = 0; $seq = $escapeChar.$c;
            while($i = strpos($str, $seq, $start)){
                $start = $i + 1;
                if( array_key_exists($c, $indicies) && (in_array($i, $indicies[$c]) || end($indicies[$c]) + strlen($seq) > $i) ) continue;
                $indicies[$c][] = $i;
            }
        }
        $escLen = strlen($escapeChar);
        $removed = 0;
        foreach($indicies as $c => $charIndicies){
            foreach($charIndicies as $i){
                $str = substr($str, 0, $i - $removed).substr($str, $i + $escLen - $removed);
                $removed += $escLen;
            }
        }
        return $str;
    }
    
    /**
     * Returns the proper callable method for the form handler callback method given.
     * 
     * Allows users to specify callbacks using the common name without having to remember to add "FormHandler" to each class name.
     * 
     * @param callable $callback The callback method to get the true method for.
     * 
     * @return callable Returns the REAL callable method to be used for the form handler.
     */
    public static function cache_form_callback($callback){
        if( !is_string($callback) && (!is_array($callback) || !array_key_exists(0, $callback)) ) return null;
        if( (!is_string($callback) && !is_array($callback)) || (is_string($callback) && strpos($callback, '::') === false) || (is_array($callback) && !is_string($callback[0])) )
            return null;
        return get::static_callable($callback, 'FormHandler');
    }
    
    /**
     * Gets the class for the form handler.
     * 
     * @param string $name The name of the form handler class.
     * 
     * @return mixed An instance of the form handler class, or null if not found.
     */
    public static function cache_form_handler($name){
        return self::mvc_class($name, 'FormHandler', 'formHandler');
    }
    
    /**
     * Gets a database defined in config by name.
     * 
     * @param string $name The name of the database to get.
     * 
     * @return db|null An instance of the database, or null if not found.
     */
    public static function db($name){
        if( array_key_exists($name, config::$databases) )
            return db::get(config::$databases[$name]);
    }
    
    /**
     * Gets the requested controller.
     * 
     * @param string $name The name of the controller to get.
     * 
     * @return controller|null An instance of the controller, or null if not found.
     */
    public static function cache_controller($name){
        $controller = self::mvc_class($name, 'Controller', 'controller');
        if( isset($controller) ) return new secureClass($controller);
        return null;
    }
    
    /**
     * Gets the requested model.
     * 
     * @param string $name The name of the model to get.
     * @param array|object $values The values that the model should be initialized with.
     * 
     * @return model|null An instance of the model, or null if not found.
     */
    public static function cache_model($name, $values = null){
        if( !isset($values) ) $values = array();
        return model::createNew($name, $values);
    }
    
    /**
     * Gets the requested widget.
     * 
     * @param string $name The name of the widget to get.
     * 
     * @return widget|null An instance of the widget, or null if not found.
     */
    public static function cache_widget($name){
        return self::mvc_class($name, 'Widget', 'widget');
    }
    
    /**
     * Gets the requested helper.
     * 
     * @param string $name The name of the helper to get.
     * 
     * @return object|null An instance of the helper class, or null if not found.
     */
    public static function cache_helper($name){
        return self::mvc_class($name, 'Helper');
    }
    
    /**
     * Gets an instance of each of the helpers for the specified type.
     * 
     * @param string $type The type of helpers to get.
     * @param string|array $skip (repeating) When a string can repeat, else if it's an array must be the last parameter.  The list of helpers to skip.
     * 
     * @return array An array containing all the helpers for the specified type.
     */
    public static function cache_helpers($type){
        $skip = func_get_args(); array_shift($skip);
        if( count($skip) == 1 && is_array($skip[0]) ) $skip = $skip[0];
        
        $ret = array();
        if( isset(config::$defaultHelpers) && is::existset(config::$defaultHelpers, $type) ){
            foreach(config::$defaultHelpers[$type] as $varName => $helperName){
                if( in_array($helperName, $skip) ) continue;
                $key = is_string($varName) ? $varName : $helperName;
                $ret[$key] = get::helper($helperName);
            }
        }
        return $ret;
    }
    
    /**
     * Gets the requested view.
     */
    public static function cache_view($a){
        $args = func_get_args();
        if( count($args) > 3 ) throw new InvalidArgumentException('Invalid number of arguments.');
        //string name, [string layout], [array parameters]
        //array route, [controller controller], [array parameters]
        if( is_string($a) ){
            $name = $a; $layout = 'default'; $params = array(); array_shift($args);
            if( count($args) == 1 ){ //could be string|array
                $nxt = array_shift($args);
                if( is_string($nxt) ) $layout = $nxt;
                elseif( is_array($nxt) ) $params = $nxt;
                else
                    throw new InvalidArgumentException('Invalid argument, second parameter must be a string or an array.');
            }elseif( count($args) == 2 ){
                //must be string then array
                $nxt = array_shift($args);
                if( !is_string($nxt) ) throw new InvalidArgumentException('Invalid argument, second parameter must be a string.');
                $layout = $nxt;
                $nxt = array_shift($args);
                if( !is_array($nxt) ) throw new InvalidArgumentException('Invalid argument, third parameter must be an array.');
                $params = $nxt;
            }
            return new view($name, $layout, $params);
        }elseif( is_array($a) ){ //find via route
            $route = $a; $controller = null; $layout = 'default'; $params = array(); array_shift($args);
            if( count($args) == 1 ){ //could be a controller|array
                $nxt = array_shift($args);
                if( is::of_class($nxt, 'controller', true) )
                    $controller = $nxt;
                elseif( is_array($nxt) ) $params = $nxt;
                else throw new InvalidArgumentException('Invalid argument, second parameter must be a controller or an array.');
            }elseif( count($args) == 2 ){
                $nxt = array_shift($args);
                if( !is::of_class($nxt, 'controller', true) ){
                    var_dump($nxt);
                    throw new InvalidArgumentException('Invalid argument, second parameter must be a controller.');
                }
                $controller = $nxt;
                $nxt = array_shift($args);
                if( !is_array($nxt) ) throw new InvalidArgumentException('Invalid argument, third parameter must be an array.');
                $params = $nxt;
            }
            
            if( isset($controller) ){
                if( isset($controller->layout) ) $layout = $controller->layout;
                if( isset($controller->view) ) return new view($controller->view, $layout, $params);
            }
            
            $view = get::route_view($route);
            if( $view !== false ) return new view($view, $layout, $params);
            return new view('errors/view_missing', $layout, array_merge($params, array('route' => $route)));
        }else throw new InvalidArgumentException('Invalid argument, first parameter must be a string or an array.');
    }
    
    /**
     * Gets a new instance of an inherited MVC base class.
     * 
     * Basically gets instances of controllers, formHandlers, widgets, models, etc...
     * 
     * @param string $name The name of the class to get.
     * @param string $type The type of the class to get (Controller, Model, etc... - gets appended to class name).
     * @param string $inheritsFrom (optional) Makes sure that the found class inherits from the base class.
     * 
     * @return mixed Returns NULL if the class is not found, or does not inherit properly, otherwise a new instance of the class.
     */
    private static function mvc_class($name, $type, $inheritsFrom = null){
        $cname = $name.$type;
        if( class_exists($cname) && ($inheritsFrom === null || is_subclass_of($cname, $inheritsFrom)) )
            return new $cname();
        return null;
    }
    
    /**
     * Generates a random string from the given set.
     * 
     * @param int $length
     *   The length of the string to create.
     * 
     * @param char $set
     *   The character set to use when generating the string.
     *   - d: digits
     *   - h: hex
     *   - u: alpha uppercase
     *   - l: alpha lowercase
     *   - o: alpha uppercase and lowercase only
     *   - A: alpha numeric uppercase
     *   - a: alpha numeric lowercase
     *   - m: mixed
     */
    public static function rand_string($length, $set = 'm'){
	    $a = array('d', 'h', 'u', 'l', 'A', 'a', 'm', 'o');
        $s = 'm';
        if( $set != 'A' ) $set = strtolower($set);
        if( in_array($set, $a) ) $s = $set;
        
        $random = '';
        mt_srand((double)microtime() * 1000000);
        $chars = get::getCharacterSet($s);
        while( strlen($random) < $length )
            $random .= substr($chars, mt_rand() % strlen($chars), 1);
        return $random;
    }
    
    /**
     * Generates a character set.
     * 
     * @param char $s The character set to generate.
     *   - d: digits
     *   - h: hex
     *   - u: alpha uppercase
     *   - l: alpha lowercase
     *   - o: alpha uppercase and lowercase only
     *   - A: alpha numeric uppercase
     *   - a: alpha numeric lowercase
     *   - m: mixed
     * 
     * @return string
     */
    private static function getCharacterSet($s){
        $digits    = '0123456789';
        $hexDigits = '0123456789ABCDEF';
        $upper     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower     = 'abcdefghijklmnopqrstuvwxyz';
        $chars = '';
        switch($s){
            case 'o':
                $chars = get::randomizeString($upper).get::randomizeString($lower); break;
            case 'd':
                $chars = get::randomizeString($digits); break;
            case 'h':
                $chars = get::randomizeString($hexDigits); break;
            case 'u':
                $chars = get::randomizeString($upper); break;
            case 'l':
                $chars = get::randomizeString($lower); break;
            case 'A':
                $chars = get::randomizeString($digits).get::randomizeString($upper).get::randomizeString($digits); break;
            case 'a':
                $chars = get::randomizeString($digits).get::randomizeString($lower).get::randomizeString($digits); break;
            case 'm':
                $chars = get::randomizeString($digits).get::randomizeString($upper).get::randomizeString($digits).get::randomizeString($lower).get::randomizeString($digits); break;
        }
        return $chars;
    }
    
    /**
     * Rearranges a given string randomly.
     * 
     * @param string $s The string to randomize.
     * 
     * @return string
     */
    private static function randomizeString($s){
        $ret = '';
        mt_srand((double)microtime() * 1000000);
        while( strlen($s) > 0 ){
            $i = mt_rand() % strlen($s);
            $ret .= substr($s, $i, 1);
            $s = substr_replace($s, '', $i, 1);
        }
        return $ret;
    }
    
    /**
     * Returns a string for a class static callable method, with the class modified.
     * 
     * @param callable $callback    The callback method to modify and return.
     * @param string $appendToClass The string to append to the class for the new class name.
     * 
     * @return callable A callable string in the format class::function.
     */
    private static function static_callable($callback, $appendToClass = ''){
        if( is_string($callback) ){
            $parts = explode('::', $callback);
            return sprintf('%s%s::%s', $parts[0], $appendToClass, $parts[1]);
        }elseif( is_array($callback) ){
            return sprintf('%s%s::%s', $callback[0], $appendToClass, $callback[1]);
        }
        log::error('Invalid callback specified, unable to return static method string.');
    }
    
    /**
     * Gets the full extension (multi-dotted) of the given file name.
     * 
     * @param string $name
     *   The name of the file you want to get the extension of.
     * 
     * @return string
     *   Returns a string containing the extension of the file, without preceding
     *   dot.  Returns a null if it could not find an extension.
     */
    public static function fullext($name){
        if( !isset($name) || strlen(trim($name)) < 3 ) return null;
        $pos = strpos($name, '.');
        if( $pos === false ) return null;
        return strtolower(substr($name, $pos + 1));
    }
    
    /**
     * Extracts the specified key from the array.
     * 
     * Extracts the key value from the given array, and makes sure that the value is an allowed value.
     * The key will no longer exist in the array after this function is run.
     * 
     * @param array $array         The array to extract a value from.
     * @param mixed $key           The key to extract from the array.
     * @param mixed $default       The default value to use should the key not exist, or is invalid.
     * @param array $allowed       A list of values that this key is allowed to be.
     * @param boolean $doNotRemove Defaults to false.  If TRUE then the key is NOT removed from the array.
     * 
     * @return Returns the value of the key, or the default value if the key is not found.
     */
    public static function array_default(array &$array, $key, $default = null, array $allowed = array(), $doNotRemove = false){
        $doNotRemove = (isset($doNotRemove) && is_bool($doNotRemove)) ? $doNotRemove : isset($doNotRemove);
        
        $return = $default;
        if( is_array($array) && array_key_exists($key, $array) && (count($allowed) < 1 || in_array($array[$key], $allowed)) )
            $return = $array[$key];
        if( !$doNotRemove && is_array($array) && array_key_exists($key, $array) )
            unset($array[$key]);
        return $return;
    }
    
    /**
     * Alias for get::array_default that is non-destructive.
     * 
     * @param array $array         The array to extract a value from.
     * @param mixed $key           The key to extract from the array.
     * @param mixed $default       The default value to use should the key not exist, or is invalid.
     * @param array $allowed       A list of values that this key is allowed to be.
     * 
     * @return Returns the value of the key, or the default value if the key is not found.
     */
    public static function array_def(array &$array, $key, $default = null, array $allowed = array()){
        return get::array_default($array, $key, $default, $allowed, true);
    }
    
    /**
     * Takes an array of HTML attributes, and formats them for the HTML tag.
     * 
     * @param array $attributes The array of attributes to format.
     * 
     * @return string The formatted attributes as a string, prepended with a space.
     */
    public static function formattedAttributes(array $attributes){
        $attributes = array_filter($attributes);
        $return = array();
        foreach($attributes as $name => $value)
            $return[] = sprintf('%s="%s"', $name, get::encodedAttribute($value));
        if( count($return) < 1 ) return '';
        return ' '.implode(' ', $return);
    }
    
    /**
     * Converts only quotation marks (", '), ampersands (&), and left and right
     * angle brackets (<, >) to their equivalent chracter entities.
     * 
     * NOT USED?
     * 
     * @param string $text The string to encode.
     * 
     * @preturn string The HTML encoded string.
     */
    public static function encodedAttribute($text){
        if( !is_string($text) || $text === null ) return $text;
        $flags = ENT_QUOTES | ENT_HTML401;
        $srcEncoding = mb_detect_encoding($text, mb_detect_order(), true);
        if( $srcEncoding === false || $srcEncoding == 'ASCII' ) $srcEncoding = 'UTF-8';
        return htmlentities($text, $flags, $srcEncoding, false);
    }
    
    /**
     * Parses the current URL to determine the route (controller/action) to use.
     * 
     * @param string $url
     *   The URL to parse for routes. If no URL is given the the current URL is used.
     * 
     * @return array
     *   - controller: The controller
     *   - action: The action
     *   - params: Array of parameters for the action.
     */
    public static function route($url = null){
        if( !isset($url) )
            $url = get::url();
        
        $ret = array('controller' => 'index', 'action' => 'index', 'params' => array());
        $parsed = parse_url($url);
        if( isset($parsed['path']) ){
            $parts = explode('/', $parsed['path']);
            $parts = array_values(array_filter($parts, create_function('$v', 'return (strlen(trim($v)) > 0);')));
            if( isset($parts) && is_array($parts) && count($parts) > 0 ){
                $bparts = array_values(array_filter(explode('/', MUNLA_WEB_ROOT), create_function('$v', 'return (strlen(trim($v)) > 0);')));
                if( isset($bparts) && is_array($bparts) && count($bparts) > 0 ){
                    //we must remove the root of the webapp otherwise apps that are in subdirectories don't get the
                    //proper controllers or actions.
                    $temp = array();
                    for($i = 0; $i < count($parts); $i++){
                        if( array_key_exists($i, $bparts) && strtolower($bparts[$i]) == strtolower($parts[$i]) ) continue;
                        $temp[] = $parts[$i];
                    }
                    $parts = $temp;
                }
                if( count($parts) > 0 ){
                    $ret['controller'] = urldecode(array_shift($parts));
                    if( count($parts) > 0 )
                        $ret['action'] = urldecode(array_shift($parts));
                    if( count($parts) > 0 )
                        $ret['params'] = array_map('urldecode', $parts);
                }
            }
        }
        return $ret;
    }
    
    /**
     * Returns the current domain name.
     * 
     * Example: www.google.com returns google.com
     *          ec2.www.google.com returns google.com
     * 
     * @return string
     *   The current domain name.
     */
    public static function cache_domain($host = null){
        if( $host == null ) $host = get::passeddomain(); //$_SERVER['SERVER_NAME'];
        $last = strrpos($host, '.');
        if( $last === false )
            return $host;
        $last = strrpos($host, '.', $last - strlen($host) - 1);
        if( $last === false )
            return $host;
        return substr($host, $last + 1);
    }
    
    /**
     * Returns the current subdomain.
     * 
     * The current subdomain could be empty (user went to google.com rather than www.google.com).
     * In this case the passed $default will be used as the current subdomain.
     * 
     * Example: www.google.com returns www
     *          ec2.www.google.com returns ec2.www
     *          google.com returns '' or when passed www as the default 'www'
     * 
     * @param string $default
     *   The default subdomain to use in the absence of a subdomain.
     * 
     * @return string
     *   The current subdomain.
     */
    public static function cache_subdomain($host = null, $default = ''){
        if( $host == null ) $host = get::passeddomain(); //$_SERVER['SERVER_NAME'];
        $last = strrpos($host, '.');
        if( $last === false )
            return $default;
        $last = strrpos($host, '.', $last - strlen($host) - 1);
        if( $last === false )
            return $default;
        return substr($host, 0, $last);
    }
    
    /**
     * Returns the current domain name.
     * 
     * With null parameters this is essentially a wrapper for $_SERVER['SERVER_NAME'].
     * 
     * @param string (optional) $subdomain  The subdomain to combine with the host.
     * @param string (optional) $host       The host to combine with the subdomain.
     * 
     * @return string  The full domain name (ex. www.one.two.domain.com, where www.one.two is the subdomain)
     */
    public static function cache_fulldomain($subdomain = null, $host = null){
        if( !isset($subdomain) && !isset($host) ) return get::passeddomain(); //$_SERVER['SERVER_NAME'];
        if( !isset($subdomain) ) $subdomain = get::subdomain();
        if( !isset($host) ) $host = get::domain();
        return sprintf('%s.%s', $subdomain, $host);
    }
    
    /**
     * Gets the ACTUAL full domain of the target page.
     * 
     * When using isolated subdomain and shared ssl, $_GET['r_domain'] contains the original
     * subdomain and should be used when doing any domain checking.
     * 
     * @return string The full domain name.
     */
    public static function cache_passeddomain(){
        $domain = $_SERVER['SERVER_NAME'];
        if( is::existset($_SERVER, 'QUERY_STRING') ){
            parse_str($_SERVER['QUERY_STRING'], $sqs);
            if( is::existset($sqs, 'r_domain') )
                $domain = sprintf('%s.%s', $sqs['r_domain'], get::domain($domain));
        }
        return $domain;
    }
    
    /**
     * Returns the first non-null value from a list of values.
     * 
     * @param mixed $value
     *   If the only parameter and it is an array, this will be
     *   used as the list of values - otherwise it uses all the
     *   arguments as the list of values.
     * 
     * @return mixed
     *   Returns the first non-null value from the provided list.
     *   Returns null if no value is found.
     */
    public static function notnull($value){
        $args = func_get_args();
        if( count($args) == 1 && is_array($args[0]) ) $args = $args[0];
        foreach($args as $v){
            if( isset($v) )
                return $v;
        }
        return null;
    }
    
    /**
     * Gets the named view file given a route.
     * 
     * @param array $route The route to find the view for.
     * 
     * @return string|bool The name of the view for the route, or FALSE on failure.
     */
    private static function route_view(array $route){
        $file = get::mvc_file('view', sprintf('%s/%s', $route['controller'], $route['action']));
        if( $file !== false ) return sprintf('%s/%s', $route['controller'], $route['action']);
        
        $file = get::mvc_file('view', sprintf('%s/index', $route['controller']));
        if( $file !== false ) return sprintf('%s/index', $route['controller']);
        
        $file = get::mvc_file('view', $route['controller']);
        if( $file !== false ) return $route['controller'];
        
        if( $route['controller'] == 'index' && $route['action'] != 'index' ){
            $file = get::mvc_file('view', $route['action']);
            if( $file !== false ) return $route['action'];
        }
        
        if( isset($route['params']) && is_array($route['params']) && count($route['params']) > 0 ){
            $filename = sprintf('%s/%s/%s', $route['controller'], $route['action'], implode('/', $route['params']));
            $file = get::mvc_file('view', $filename);
            if( $file !== false ) return $filename;
        }
        return false;
    }
    
    /**
     * Gets a replaceable file.
     * 
     * @param string $type
     *   The type of file to get.  Influences the path.  Pluralized by lowercase
     *   and adding 's'. The path would then be BASE_DIR/munla_core/plural/name.php or
     *   APP_DIR/mvc/plural/name.php.
     * 
     * @param string $name
     *   The name of the file to get.  May not contain any periods.  May contain
     *   forward slashes (/) to denote subdirectories. APP_DIR/mvc/plural/na/me.php
     * 
     * @return mixed
     *   When the file is found, the path to the file is returned, otherwise it
     *   returns a boolean false.
     */
    public static function mvc_file($type, $name){
        if( strpos($type, '.') !== false || strpos($name, '.') !== false ) return false;
        $typeFolder = strtolower($type).'s';
        $paths = array(MUNLA_APP_DIR.'mvc/', MUNLA_CORE_DIR);
        $ret = false;
        foreach($paths as $path){
            $file = sprintf('%s%s/%s.php', $path, $typeFolder, strtolower($name));
            if( file_exists($file) ){
                $ret = $file;
                break;
            }
        }
        return $ret;
    }
    
    /**
     * Gets a replaceable directory.
     * 
     * @param string $type
     *   The type of file to get.  Influences the path.  Pluralized by lowercase
     *   and adding 's'. The path would then be BASE_DIR/munla_core/plural/name.php or
     *   APP_DIR/mvc/plural/name.php.
     * 
     * @param string $name
     *   The name of the directory to get.  May not contain any periods.  May contain
     *   forward slashes (/) to denote subdirectories. APP_DIR/mvc/plural/na/me
     * 
     * @return mixed
     *   When the directory is found, the path to the directory is returned, otherwise it
     *   returns a boolean false.
     */
    public static function mvc_dir($type, $name){
        if( strpos($type, '.') !== false || strpos($name, '.') !== false ) return false;
        $typeFolder = strtolower($type).'s';
        $paths = array(MUNLA_APP_DIR.'mvc/', MUNLA_CORE_DIR);
        $ret = false;
        foreach($paths as $path){
            $file = sprintf('%s%s/%s', $path, $typeFolder, strtolower($name));
            if( file_exists($file) && is_dir($file) ){
                $ret = $file;
                break;
            }
        }
        return $ret;
    }
    
    /**
     * Gets the last url remembered by the application.
     * 
     * @return string Returns the url, or if no url is remembered the url for the homepage.
     */
    public static function last_url(){
        if( isset(munla::$session['lastpage']) && strlen(munla::$session['lastpage']) > 0 )
            return munla::$session['lastpage'];
        return get::url('index', false);
    }
    
    /**
     * Gets a url.
     * 
     * A robust method of getting url, with many options depending on the paramters.
     * With no parameters, it gets the current url.
     * With a single string parameter
     *   starts with "www.": return scheme with the parameter
     *   already a url: returns the url
     *   a FILE path: url to file, or just the filename if the file is inaccessible to the DOCUMENT_ROOT
     *   the path after the domain: full url with the path replace with the given path
     *   equals 'http' or 'https': converts current url to http or https
     * 
     * Multiple parameters (see parameters for details).  Multiple parameters may also be passed
     * as an associative array.
     * 
     * Any parameter may be skipped, though they must be in order.
     * 
     * Some parameters require prior parameters to be passed (such as argSeparator) otherwise
     * it may be mistaken for an eariler parameter.
     * 
     * @param string $webpath      The path following the domain name.
     * @param string|bool $https   Whether the url should be secure or not. Valid values: true, false, 'http', 'https'.
     * @param int $port            The port number that should be used (ex. http://www.google.com:57/).
     * @param string|array $get    The query string that should be appended to the url.
     * @param string $argSeparator The separator that should splite arguements in the query string.
     * 
     * @return string Returns the url, or just the filename for inaccessible file paths.
     */
    public static function cache_url(){
        // get the arguements
        $args = func_get_args(); $noArgs = (count($args) == 0 || (count($args) == 1 && is_bool($args[0])));
        $fallback = null;
        if( count($args) == 1 && is_string($args[0]) ){
            if( strlen($args[0]) > 4 && strtolower(substr($args[0], 0, 4)) == 'www.' )
                return 'http'.(is::ssl() ? 's' : '').'://'.$args[0];
            $file_url = self::file_url($args[0]);
            if( is::url($file_url) ) return $file_url;
            //if( file_exists($args[0]) || file_exists(MUNLA_APP_DIR.$args[0]) ) return self::file_url($args[0];//file url
            if( is::url($args[0]) ) return $args[0];
            $fallback = $args[0];
        }
        
        $webpath = null; $https = null; $port = null; $get = null; $argSeparator = '&';
        if( count($args) == 1 && is_array($args[0]) ){
            $vargs = array('webpath', 'https', 'port', 'get', 'argSeparator');
            $is_assoc = false; //must use this method rather than is::assoc_array because GET can be an assoc array
            foreach($vargs as $v){
                if( is::existset($args[0], $v) ){
                    $is_assoc = true;
                    break;
                }
            }
            if( $is_assoc ){
                //arguements were passed as associative array
                if( is::existset($args[0], 'webpath') ) $webpath = $args[0]['webpath'];
                if( is::existset($args[0], 'https') ) $https = ($args[0]['https'] == 'https' || $args[0]['https'] === true);
                if( is::existset($args[0], 'port') ) $port = $args[0]['port'];
                if( is::existset($args[0], 'get') ) $get = $args[0]['get'];
                if( is::existset($args[0], 'argSeparator') ) $argSeparator = $args[0]['argSep'];
            }else
                $get = $args[0]; //not an associative array, the array is meant for get
        }else{
            // cycle through the arguments and assign them based on type
            // remember to not go out of order
            // webpath,  https,      port,     get,      argSeparator
            // string , bool|string,  int, string|array, string
            $argPos = 0;
            while(count($args) > 0){
                $arg = array_shift($args);
                if( is_string($arg) ){
                    $argl = strtolower($arg);
                    if( $argPos <= 1 && ($argl == 'https' || $argl == 'http') ){ $argPos = 2; $https = ($argl == 'https'); continue; }
                    if( $argPos > 0 && $argPos <= 3 ){
                        if( strlen($arg) > 1 ) $get = $arg;
                        $argPos = 4;
                        continue;
                    }
                    if( $argPos > 3 ){ $argSeparator = $arg; break; }
                    if( $argPos < 1 ){ $webpath = $arg; $argPos = 1; continue; }
                }
                if( $argPos <= 1 && is_bool($arg) ){  $https = $arg; $argPos = 2; }
                if( $argPos <= 2 && is_int($arg) ){ $port = $arg; $argPos = 3; }
                if( $argPos <= 3 && is_array($arg) ){ $get = $arg; $argPos = 4; }
            }
        }
        //var_dump(array('webpath' => $webpath, 'https' => $https, 'port' => $port, 'get' => $get, 'argSeparator' => $argSeparator));
        if( !isset($https) ) $https = is::ssl();
        
        if( isset($webpath) && is::url($webpath) ){
            if( strlen($webpath) > 4 && strtolower(substr($webpath, 0, 4)) == 'www.' )
                return 'http'.($https ? 's' : '').'://'.$webpath;
            if( is::url($webpath) ){
                //convert http to https or https to http
                if( strtolower(substr($webpath, 0, 6)) == 'http:/' &&  $https ) return 'https:/'.substr($webpath, 6);
                if( strtolower(substr($webpath, 0, 6)) == 'https:' && !$https ) return 'http:'.substr($webpath, 6);
                return $webpath;
            }
        }
        
        //get the port number to use - only use if it doesn't match the default
        $portNum = (isset($port) && ctype_digit((string)$port)) ? ':'.$port : '';
        if( $portNum == '' ){
            if( $https ){
                if( config::$https_port != 443 )
                    $portNum = ':'.config::$https_port;
            }else{
                if( config::$http_port != 80 )
                    $portNum = ':'.config::$http_port;
            }
        }
        
        // convert get into an array
        if( $noArgs && !isset($get) ){
            if( is::existset($_SERVER, 'QUERY_STRING') )
                parse_str($_SERVER['QUERY_STRING'], $get);
        }else{
            if( isset($get) && is_string($get) ){ $gs = $get; $get = array(); parse_str($gs, $get); }
        }
        if( !isset($get) || !is_array($get) ) $get = array();
        
        // get the domain
        $domain = $_SERVER['SERVER_NAME'];
        if( $https && isset(config::$https_domain) && $https != is::ssl() && $_SERVER['SERVER_NAME'] != config::$https_domain ){
            //shared ssl domain, pass original domain to GET
            $domain = config::$https_domain;
            $get['r_domain'] = get::subdomain(null, 'www'); //$_SERVER['SERVER_NAME'];
        }elseif( !$https && $https != is::ssl() ){
            //non-ssl find non-ssl domain (for shared SSL domains)
            $found = false;
            if( is::existset($_SERVER, 'QUERY_STRING') ){
                parse_str($_SERVER['QUERY_STRING'], $sqs);
                if( is::existset($sqs, 'r_domain') ){
                    $domain = get::fulldomain($sqs['r_domain']);
                    $found = true;
                }
            }
            //fallback to config if set
            if( !$found && isset(config::$http_domain) )
                $domain = config::$http_domain;
            
            if( isset($get['r_domain']) ) unset($get['r_domain']);
        }elseif( $https && isset(config::$https_domain) && !isset($get['r_domain']) ){
            if( is::existset($_SERVER, 'QUERY_STRING') ){
                parse_str($_SERVER['QUERY_STRING'], $sqs);
                if( is::existset($sqs, 'r_domain') )
                    $get['r_domain'] = $sqs['r_domain'];
            }
        }
        
        // if webpath isn't set - use the current webpath
        if( !isset($webpath) ){
            $qs = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
            $webpath = substr($_SERVER['REQUEST_URI'], 0, strlen($_SERVER['REQUEST_URI']) - strlen($qs));
            if( substr($webpath, -1) == '?' ) $webpath = substr($webpath, 0, -1);
        }
        
        // if the webpath doesn't start with the web root, then prepend the web root
        if( strlen($webpath) > 0 && substr($webpath, 0, 1) != '/' ) $webpath = '/'.$webpath; //must run twice for proper root check
        if( substr($webpath, 0, strlen(MUNLA_WEB_ROOT)) != MUNLA_WEB_ROOT )
            $webpath = MUNLA_WEB_ROOT.(substr($webpath, 0, 1) == '/' ? substr($webpath, 1) : $webpath);
        $webpath = preg_replace('/^(\/?index(\.php)?)(\/?index(\.php)?)?/i', '', $webpath);
        if( strlen($webpath) > 0 && substr($webpath, 0, 1) != '/' ) $webpath = '/'.$webpath;
        
        // if a query string modifier is present - append it to the query string
        if( defined('QS_APPEND') ){
            if( !isset($get) ) $get = QS_APPEND;
            elseif( is_array($get) ){
                if( is_array(QS_APPEND) ) $get = $get + QS_APPEND;
                else{
                    $prefix = array();
                    parse_str(QS_APPEND, $prefix);
                    $get = $get + $prefix;
                }
            }elseif( is_string($get) ){
                $pfx = (strlen($get) > 0) ? $argSeparator : '';
                if( !is_array(QS_APPEND) ) $get = $argSeparator.QS_APPEND;
                else $get .= $argSeparator.http_build_query(QS_APPEND, '', $argSeparator);
            }
        }
        
        //build the query string
        $qs = (isset($get) && is_array($get) && count($get)) ?
                '?'.http_build_query($get, '', $argSeparator) :
                ((isset($get) && is_string($get)) ? '?'.$get : '');
        
        //if built url is not valid - return fallback
        $webpath = implode('/', array_map('urlencode', explode('/', $webpath)));
        $url = sprintf('%s://%s%s%s%s', ($https ? 'https' : 'http'), $domain, $portNum, $webpath, $qs);
        if( !is::url($url) ){
            if( !isset($fallback) ) return sprintf('%s://%s%s', ($https ? 'https' : 'http'), $domain, $portNum);
            return $fallback;
        }
        return $url;
    }
    
    /**
     * Attempts to construct the full URL for the given file.
     * 
     * Files that match a url (begin with http, ftp, or https followed by
     * ://) will be returned as is.
     * Files that appear to be a url (start with www.) will have the
     * current scheme prepended to them.
     * 
     * All others will be checked to see if the file exists relative to
     * the document root, and the current URL directory (request came from
     * http://www.mysite.com/path/two/otherfile.php then it would check 
     * both relative to http://www.mysite.com/path/two/ and http://www.mysite.com/.
     * If the file exists and is within the document root (can't have anyone
     * accessing system files using ../../ patterns) it will return the url
     * to the file.
     * 
     * If it cannot match as a URL or a valid file, it returns the passed
     * file exactly as it is.
     * 
     * Example:
     * Called from - http://www.mysite.com/path/file.php
     * File exists - http://www.mysite.com/css/layout.css
     * File exists - /var/systemfile (/var/www being the document root)
     * $file -> $return
     * css/layout.css -> http://www.mysite.com/css/layout.css
     * ../css/layout.css -> http://www.mysite.com/css/layout.css
     * http://www.othersite.com/file.pdf -> http://www.othersite.com/file.pdf
     * www.otheriste.com/file.pdf -> http://www.othersite.com/file.pdf
     * ../../systemfile -> systemfile (would result it broken link)
     * css/notthere.css -> css/notthere.css
     * 
     * @param string $file
     *   The file to create the URL for.
     * 
     * @return string
     *   The full URL of the file specified or the filename as given.
     *   If outside the server document root, the filename only.
     */
    public static function cache_file_url($file){
        if( preg_match('/^(http|ftp|https):\/\//', $file) )
            return $file;
        
        if( strlen($file) > 4 && strtolower(substr($file, 0, 4)) == 'www.' )
            return 'http'.(is::ssl() ? 's' : '').'://'.$file;
        
        $docroot = strtr($_SERVER['DOCUMENT_ROOT'], '\\', '/');
        $self = strtr($_SERVER['PHP_SELF'], '\\', '/');
        if( substr($docroot, -1) != '/' ) $docroot .= '/';
        if( substr($self, 0, 1) == '/' ) $self = substr($self, 1);
        $base_dir = get::dirname($docroot.$self);
        if( substr($base_dir, -1) != '/' ) $base_dir .= '/';
        
        if( strlen($file) > strlen($docroot) && strtolower(substr($file, 0, strlen($docroot))) == strtolower($docroot) )
            $file = substr($file, strlen($docroot));
        
        //try relative (from basename of URL file, and server docroot)
        if( file_exists($base_dir.$file) || file_exists($docroot.$file) ){
            $path = get::realpath((file_exists($base_dir.$file) ? $base_dir.$file : $docroot.$file));
            if( $path !== false && strtolower(substr($path, 0, strlen($docroot))) == strtolower($docroot) ){
                //file is within the website
                $self_url = self::url(); if( $self_url == null ){ define('DEBUG_URL', true); $self_url = self::url(); log::debug($self_url); define('URL_DEBUGGED', true); }
                $current = parse_url($self_url);
                $temp = '';
                if( isset($current['user']) && isset($current['pass']) )
                    $temp .= sprintf('%s:%s@', $current['user'], $current['pass']);
                $temp .= $current['host'];
                if( isset($current['port']) )
                    $temp .= ':'.$current['port'];
                return $current['scheme'].'://'.str_replace('//', '/', $temp.'/'.substr($path, strlen($docroot)));
            }else{
                //file is outside of the website - hacking attempt
                return basename($file);
            }
        }
        
        return $file;
    }
    
    /**
     * Attempts to get the file path of the given file (as relative to the URL).
     * 
     * @param string $file The relative path of the file to find.
     * 
     * @return string|bool Returns the path to the local file, or boolean FALSE on failure.
     */
    public static function cache_file_path($file){
        $docroot = strtr($_SERVER['DOCUMENT_ROOT'], '\\', '/');
        $self = strtr($_SERVER['PHP_SELF'], '\\', '/');
        if( substr($docroot, -1) != '/' ) $docroot .= '/';
        if( substr($self, 0, 1) == '/' ) $self = substr($self, 1);
        $base_dir = get::dirname($docroot.$self);
        if( substr($base_dir, -1) != '/' ) $base_dir .= '/';
        
        if( strlen($file) > strlen($docroot) && strtolower(substr($file, 0, strlen($docroot))) == strtolower($docroot) )
            $file = substr($file, strlen($docroot));
        
        //try relative (from basename of URL file, and server docroot)
        if( file_exists($base_dir.$file) || file_exists($docroot.$file) ){
            $path = get::realpath((file_exists($base_dir.$file) ? $base_dir.$file : $docroot.$file));
            if( $path !== false && strtolower(substr($path, 0, strlen($docroot))) == strtolower($docroot) ){
                //file is within the website
                return $path;
            }
        }
        //file is outside of the website - hacking attempt (or doesn't exist)
        return false;
    }
    
    /**
     * Attempts to get the file path of the given directory (as relative to the URL).
     * 
     * @param string $file The relative path of the directory to find.
     * 
     * @return string|bool Returns the path to the local directory, or boolean FALSE on failure.
     */
    public static function cache_dir_path($file){
        $docroot = strtr($_SERVER['DOCUMENT_ROOT'], '\\', '/');
        $self = strtr($_SERVER['PHP_SELF'], '\\', '/');
        if( substr($docroot, -1) != '/' ) $docroot .= '/';
        if( substr($self, 0, 1) == '/' ) $self = substr($self, 1);
        $base_dir = get::dirname($docroot.$self);
        if( substr($base_dir, -1) != '/' ) $base_dir .= '/';
        
        if( strlen($file) > strlen($docroot) && strtolower(substr($file, 0, strlen($docroot))) == strtolower($docroot) )
            $file = substr($file, strlen($docroot));
        
        //try relative (from basename of URL file, and server docroot)
        if( file_exists($base_dir.$file) && is_dir($base_dir.$file) ){
            $path = get::realpath($base_dir.$file);
            if( $path !== false && strtolower(substr($path, 0, strlen($docroot))) == strtolower($docroot) ){
                //file is within the website
                return $path;
            }
        }
        if( file_exists($docroot.$file) && is_dir($docroot.$file) ){
            $path = get::realpath($docroot.$file);
            if( $path !== false && strtolower(substr($path, 0, strlen($docroot))) == strtolower($docroot) ){
                //file is within the website
                return $path;
            }
        }
        //file is outside of the website - hacking attempt (or doesn't exist)
        return false;
    }
    
    /**
     * Wrapper for internal function dirname to always provide
     * the path with forward slashes.
     * 
     * See http://us2.php.net/manual/en/function.dirname.php for usage.
     * 
     * @param string $path
     *   A path.  On Windows, both slash (/) and backslash (\) are used
     *   as directory separator character. In other environments, it is
     *   the forward slash (/).
     * 
     * @return string
     *   Returns the path of the parent directory. If there are no slashes
     *   in path, a dot ('.') is returned, indicating the current directory.
     *   Otherwise, the returned string is path with any trailing /component
     *   removed.
     */
    public static function dirname($path){
        return strtr(dirname($path), '\\', '/');
    }
    
    /**
     * Wrapper for internal function realpath to always provide
     * the path with forward slashes.
     * 
     * See http://us2.php.net/manual/en/function.realpath.php for usage.
     * 
     * @param string $path
     *   The path being checked.
     * 
     * @return mixed
     *   Returns the canonicalized absolute pathname on success. The 
     *   resulting path will have no symbolic link, '/./' or '/../' components.
     *   
     *   realpath() returns FALSE on failure, e.g. if the file does not exist.
     */
    public static function realpath($path){
        $r = realpath($path);
        if( $r === false ) return $r;
        return strtr($r, '\\', '/');
    }
}
