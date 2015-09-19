<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * log
 * 
 * Provides for error logging capabilities.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class log{
    
    private static $inUserHandler = false; //prevents errors in the custom user handler from causing loops
    private static $debugMessages = array(); //stores debug messages to be output via HTML
    private static $jsMessages = array(); //stores debug messages to be output via JS
    private static $consoleSetup = false; //stores whether the console has already been setup
    public static $errorDetails = null; //stores extra data that can be used for database debugging.
    
    /**
     * If XDebug is installed starts the trace, with full variable contents and variable names.
     * 
     * @param string $trace_file The file to put the trace log in
     * @param int $options
     * 
     * @return void
     */
    public static function start_trace($trace_file, $options = null){
        if( function_exists('xdebug_start_trace') ){
            ini_set('xdebug.collect_params', 4);
            if( $options !== null )
                xdebug_start_trace($trace_file, $options);
            else
                xdebug_start_trace($trace_file);
        }else{
            self::notice('xdebug is not installed');
        }
    }
    
    /**
     * If an XDebug trace is running, this stops the trace.
     * 
     * @param bool $showAsDebug  If true, outputs the trace file as debug information (assuming displayDebug is used)
     * 
     * @return void
     */
    public static function end_trace($showAsDebug = false){
        if( function_exists('xdebug_stop_trace') ){
            $file = xdebug_get_tracefile_name();
            xdebug_stop_trace();
            if( $showAsDebug === true ){
                $trace = file_get_contents($file);
                self::debug($trace);
            }
        }
    }
    
    /**
     * Triggers an error with the given message.
     * 
     * @param string $msg The error message.
     * 
     * @return void
     */
    public static function error($msg){
        return log::trigger_error(E_USER_ERROR, $msg);
    }
    
    /**
     * Triggers a warning message.
     * 
     * @param string $msg The warning message.
     * 
     * @return void
     */
    public static function warning($msg){
        return log::trigger_error(E_USER_WARNING, $msg);
    }
    
    /**
     * Triggers a notice message.
     * 
     * @param string $msg The notice message.
     * 
     * @return void
     */
    public static function notice($msg){
        return log::trigger_error(E_USER_NOTICE, $msg);
    }
    
    /**
     * Triggers an error
     * 
     * Serializes data to be passed to the error handler, such as message, file and line number.
     * That data is then submitted to trigger_error - and the serialized message must be under 1024 bytes.
     * Long messages/file names could cause problems - though situations like that are not common.
     * 
     * @param int $level The designated error type for this error (use E_USER family of constants).
     * @param string $message The error message for this error (limited to 1024 bytes)
     * 
     * @return void
     */
    protected static function trigger_error($level, $message){
        if( !config::DEBUG_MODE && in_array($level, array(E_USER_WARNING, E_USER_NOTICE)) )
            return;
        
        if( !is_string($message) ){
            ob_start();
            var_dump($message);
            $message = ob_get_contents();
            ob_end_clean();
        }
        $caller = null;
        $nonLog = null;
        $nonMunla = null;
        $stack = debug_backtrace();
        next($stack);
        list($k, $v) = each($stack);
        do{
            if( !isset($v['file']) ) continue;
            if( !isset($caller) ) $caller = $v;
            if( !isset($nonLog) && $v['file'] != __FILE__ && (!array_key_exists('class', $v) || $v['class'] != 'log') && !in_array($v['function'], array('error', 'warning', 'notice', 'debug')) ) 
                $nonLog = $v;
            if( !isset($nonMunla) && strtolower(substr($v['file'], 0, strlen(MUNLA_CORE_DIR))) != MUNLA_CORE_DIR ){
                $nonMunla = $v;
                break;
            }
        }while(list($k, $v) = each($stack));
        $caller = get::notnull($nonMunla, $nonLog, $caller); //get::notnull($nonMunla, $nonLog, $caller);
        if( !isset($caller) && count($stack) > 0 ){
            $caller = (count($stack) > 1) ? $stack[1] : $stack[0];
        }elseif( !isset($caller) ){
            $caller = array('file' => __FILE__, 'line' => __LINE__);
        }
        
        //$errorMessage = serialize(array('message' => $message, 'file' => $caller['file'], 'line' => $caller['line']));
        //trigger_error($errorMessage, $level);
        self::error_handler($level, $message, $caller['file'], $caller['line'], true);
    }
    
    public static function fatal_error_handler(){
        $error = error_get_last();
        if( $error && is_array($error) && $error['type'] == E_ERROR )
            self::error_handler($error['type'], $error['message'], $error['file'], $error['line'], null);
    }
    
    /**
     * Handles errors generated by PHP, and trigger_error.
     * 
     * @param int $level      The level of the error raised.
     * @param string $message The error message.
     * @param string $file    The file the error was raised in.
     * @param int $line       The line number the error was raised in the file.
     * @param mixed $context  The context of the error.
     * 
     * @return bool If the function returns FALSE the normal error handler continues.
     */
    public static function error_handler($level, $message, $file, $line, $context){
        $userErrors = array(E_ERROR, E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
        $userReturn = false;
        if( in_array($level, $userErrors) ){
            $lvlMessage = 'Error';
            switch($level){
                case E_ERROR:
                case E_USER_ERROR:
                    $lvlMessage = 'Fatal error'; break;
                case E_USER_WARNING:
                    $lvlMessage = 'Warning'; break;
                case E_USER_NOTICE:
                    $lvlMessage = 'Notice'; break;
            }
            
            if( isset(config::$errorHandler) )
                $userReturn = self::user_handler_caller($level, $message, $file, $line, $context);
            
            if( error_reporting() === 0 )
                return true;
            
            if( !$userReturn && ini_get("display_errors") && (config::DEBUG_MODE !== 'js' || (config::DEBUG_MODE === 'js' && $level == E_USER_ERROR)) ){
                echo "<br />\n".'<b>'.$lvlMessage.'</b>:  '.$message.' in <b>'.$file.'</b> on line <b>'.$line.'</b><br />';
            }
            
            self::jsOut($level, sprintf('%s in %s on line %s', $message, $file, $line));
            switch($level){
                case E_ERROR:
                case E_USER_ERROR:
                    self::displayDebug(); exit(1); break;
            }
            return true;
        }elseif( isset(config::$errorHandler) )
            $userReturn = self::user_handler_caller($level, $message, $file, $line, $context);
        
        if( error_reporting() === 0 )
            return true;
        return $userReturn;
    }
    
    /**
     * Handles unhandled exceptions generated by PHP and turns them into
     * something the custom error handler function might be able to process.
     * 
     * @param exception $e The generated exception.
     * 
     * @return void
     */
    public static function exception_handler($e){
        $userReturn = null;
        if( isset(config::$errorHandler) )
            $userReturn = self::user_handler_caller(E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine(), $e);
        if( !isset($userReturn) ){
            printf("<br />\n<b>Fatal error</b>: Unhandled exception (%s[%s]) in <b>%s</b> (line: %d)<br />Message: %s\n",
                get_class($e), $e->getCode(), $e->getFile(), $e->getLine(), $e->getMessage());
        }elseif( $userReturn ){
            printf("<br />\n<b>Fatal Exception</b>: %s\n", $e->getMessage());
        }
    }
    
    /**
     * This handles calling any defined user error_handler script in a safe way as to
     * prevent loops from errors happening within errors.
     * 
     * @param int $level      The level of the error raised.
     * @param string $message The error message.
     * @param string $file    The file the error was raised in.
     * @param int $line       The line number the error was raised in the file.
     * @param mixed $context  The context of the error.
     * 
     * @return bool If the function returns FALSE the normal error handler continues.
     */
    protected static function user_handler_caller($level, $message, $file, $line, $context){
        if( self::$inUserHandler ) return false;
        self::$inUserHandler = true;
        
        if( !is_callable(config::$errorHandler) ){
            $msg = config::$errorHandler;
            if( is_array(config::$errorHandler) )
                $msg = sprintf('%s::%s', config::$errorHandler[0], config::$errorHandler[1]);
            printf("<br />\n<b>Fatal error</b>:  Invalid user error handler method %1$s() in <b>config.php</b>.  '%1$s' is not callable.<br />", $msg);
            exit();
        }
        $ret = call_user_func_array(config::$errorHandler, array($level, $message, $file, $line, $context));
        
        self::$inUserHandler = false;
        return $ret;
    }
    
    /**
     * Displays the debug messages that were collected so far (but does not clear them).
     * Generally this should be called just before the script finishes executing.
     * 
     * @return void
     */
    public static function displayDebug(){
        if( !config::DEBUG_MODE || config::DEBUG_MODE === 'js' || count(self::$debugMessages) < 1 ){
            if( config::DEBUG_MODE === 'js' && count(self::$jsMessages) > 0 )
                echo implode("\n", self::$jsMessages);
            return;
        }
        echo '<h2>Debug</h2><pre>';
        if( ini_get('html_errors') == '1' )
            echo implode("\n", self::$debugMessages);
        else
            echo implode("\n", array_map('htmlspecialchars', self::$debugMessages));
        echo '</pre>';
    }
    
    /**
     * This sets up the JavaScript console if the console doesn't already support the debug functions.
     * 
     * @return void;
     */
    private static function setupConsole(){
        if( self::$consoleSetup || config::DEBUG_MODE !== 'js' ) return;
        echo '<script type="text/javascript">'."\n";
        echo 'if (!window.console) console = {};'."\n";
        echo 'console.log = console.log || function(){};'."\n";
        echo 'console.warn = console.warn || function(){};'."\n";
        echo 'console.error = console.error || function(){};'."\n";
        echo 'console.info = console.info || function(){};'."\n";
        echo 'console.debug = console.debug || function(){};'."\n";
        echo '</script>'."\n";
        self::$consoleSetup = true;
    }
    
    /**
     * Outputs a debug message and/or var_dump of the given variables.
     * 
     * @param mixed $v,... unlimited The message or variable to output as a debug message.
     * 
     * @return void
     */
    public static function debug($v){
        ini_set('xdebug.var_display_max_depth', 6);
        $args = func_get_args();
        if( config::DEBUG_MODE === 'js' ){
            array_unshift($args, 'debug');
            call_user_func_array(array(__CLASS__, 'jsOut'), $args); //self::jsOut('debug', $args);
            return;
        }
        $caller = self::myBacktrace()[0];
        $caller = preg_replace('/^'.preg_replace('/\//', '\\\\\\', MUNLA_APP_DIR).'/i', '', $caller);
        foreach( $args as $arg ){
            if( $arg === null )
                self::$debugMessages[] = $caller.': __NULL__';
            else{//if( !is_string($arg) ){
                ob_start();
                var_dump($arg);
                self::$debugMessages[] = $caller.trim(ob_get_contents());
                ob_end_clean();
            //}else{
                //self::$debugMessages[] = $arg;
            }
            $caller = '';
        }
    }
    
    /**
     * Outputs a debug message and/or var_dump of the given variables.
     * 
     * Unlike debug(), this function shouldn't limit the data in xdebug's var_dump.
     * 
     * @param mixed $v,... unlimited The message or variable to output as a debug message.
     * 
     * @return void
     */
    public static function dbg($v){
        $args = func_get_args();
        
        $xdebug = function_exists('xdebug_var_dump');
        $defaults = array('xdebug.var_display_max_children' => null, 'xdebug.var_display_max_data' => null, 'xdebug.var_display_max_depth' => null);
        if( $xdebug ){
            foreach(array_keys($defaults) as $k){
                $defaults[$k] = ini_get($k);
                ini_set($k, '-1');
            }
        }
        
        call_user_func_array(array(__CLASS__, 'debug'), $args); //self::jsOut('debug', $args);
        
        if( $xdebug ){
            foreach($defaults as $k => $dv)
                ini_set($k, $dv);
        }
    }
    
    /**
     * Generates a backtrace for javascript debug messages. Omits calls to log class.
     * 
     * @return mixed Returns an array of associative arrays.
     *   See http://www.php.net/manual/en/function.debug-backtrace.php
     */
    private static function myBacktrace(){
        $trace = array();
        $stack = debug_backtrace();
        next($stack);
        list($k, $v) = each($stack);
        do{
            if( !isset($v['file']) ) continue;
            if( $v['file'] == __FILE__ )
                continue;
            $trace[] = sprintf('%s:%s', $v['file'], $v['line']);
        }while(list($k, $v) = each($stack));
        return $trace;
    }
    
    /**
     * Outputs (to our log collector) JS debug or error messages.
     * 
     * @param mixed $level Takes string "debug" or E_USER_NOTICE, E_USER_WARNING, E_USER_ERROR
     * @param mixed $obj,... The object to output as debug/error messages
     * 
     * @return void
     */
    private static function jsOut($level, $obj){
        if( config::DEBUG_MODE !== 'js' ) return;
        ob_start();
        self::setupConsole();
        $args = func_get_args();
        array_shift($args);
        $n = "\n";
        
        //$caller = self::getCaller();
        $caller = self::myBacktrace();
        echo "<script type=\"text/javascript\">\n";
        echo '/*'.$n;
        //echo 'called on '.$caller['file'].':'.$caller['line'].$n;
        $html_errors = ini_get('html_errors');
        ini_set('html_errors', 0);
        var_dump($caller);
        var_dump($args);
        ini_set('html_errors', $html_errors);
        echo $n.'*/'.$n;
        
        foreach($args as $arg){
            $id = uniqid();
            if( !isset($arg) ){
                switch($level){
                    case 'debug': echo 'console.debug(" -null-");'.$n; break;
                    case E_USER_NOTICE: echo 'console.info(" -null-");'.$n; break;
                    case E_USER_WARNING: echo 'console.warn(" -null-");'.$n; break;
                    case E_USER_ERROR: echo 'console.error(" -null-");'.$n; break;
                }
            }elseif( is_object($arg) || is_array($arg) ){
                $o = json_encode($arg);
                echo sprintf("var object%s = '%s';%s", $id, str_replace("'", "\'", $o), $n);
                echo sprintf('var val%s = eval( "(" + object%s + ")" );%s', $id, $id, $n);
                switch($level){
                    case 'debug': echo sprintf('console.debug(val%s);%s', $id, $n); break;
                    case E_USER_NOTICE: echo sprintf('console.info(val%s);%s', $id, $n); break;
                    case E_USER_WARNING: echo sprintf('console.warn(val%s);%s', $id, $n); break;
                    case E_USER_ERROR: echo sprintf('console.error(val%s);%s', $id, $n); break;
                }
            }elseif( is_bool($arg) ){
                $v = ($arg ? 'bool(true)' : 'bool(false)');
                switch($level){
                    case 'debug': echo sprintf('console.debug("%s");%s', $v, $n); break;
                    case E_USER_NOTICE: echo sprintf('console.info("%s");%s', $v, $n); break;
                    case E_USER_WARNING: echo sprintf('console.warn("%s");%s', $v, $n); break;
                    case E_USER_ERROR: echo sprintf('console.error("%s");%s', $v, $n); break;
                }
            }else{
                $v = str_replace('\\', '\\\\', $arg);
                $v = str_replace('"', '\\"', $v);
                $v = str_replace("\n", '\\n', $v);
                $v = str_replace("\r", '\\r', $v);
                switch($level){
                    case 'debug': echo sprintf('console.debug("%s");%s', $v, $n); break;
                    case E_USER_NOTICE: echo sprintf('console.info("%s");%s', $v, $n); break;
                    case E_USER_WARNING: echo sprintf('console.warn("%s");%s', $v, $n); break;
                    case E_USER_ERROR: echo sprintf('console.error("%s");%s', $v, $n); break;
                }
            }
        }
        echo "</script>\n";
        $jsBuffer = ob_get_contents();
        ob_end_clean();
        self::$jsMessages[] = $jsBuffer;
    }
}

set_error_handler(array('log', 'error_handler'), config::ERROR_LEVEL);
set_exception_handler(array('log', 'exception_handler'));
register_shutdown_function(array('log', 'fatal_error_handler'));