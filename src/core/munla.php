<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * munla
 * 
 * Munla is a simple, fast framework.
 * 
 * Web Page Request Outline
 * ------------------------
 * 
 * 0. URL Parsing
 * 1. SSL verification
 * 2. Permission verification
 * 3. Form Handling
 * 4. Action Running
 * 6. View Construct
 * 7. Output to user
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class munla{
    
    public static $starttime = null; //for timing the webpage
    public static $session = null; //shortcut for the session
    public static $user = null; //shortcut for the current user
    public static $nohistory = false; //stores whether to keep history for the current call or not
    public static $singleUse = null;
    
    //Autoloadable types
    private static $types = array('Model', 'Controller', 'FormHandler', 'Helper', 'Widget');
    
    //Basic APP defined classes
    private static $appDef = array('user', 'config', 'app');
    
    /**
     * Autoload includes class files as they are needed.
     * 
     * @param string $name The name of the class to load.
     */
    public static function autoload($name){
        $fileToRequire = false;
        if( $name == 'stdClassModel' )
            $fileToRequire = sprintf('%s%s.php', MUNLA_CORE_DIR, $name);
        
        if( $fileToRequire === false && strlen($name) > 3 && substr($name, 0, 3) == 'xml' )
            $fileToRequire = sprintf('%sxml/%s.php', MUNLA_CORE_DIR, strtolower(substr($name, 3)));
        
        if( $fileToRequire === false && in_array($name, self::$appDef) ){
            if( file_exists(sprintf('%s%s.php', MUNLA_APP_DIR, $name)) )
                $fileToRequire = sprintf('%s%s.php', MUNLA_APP_DIR, $name);
            elseif( file_exists(sprintf('%s_%s.php', MUNLA_CORE_DIR, $name)) )
                $fileToRequire = sprintf('%s_%s.php', MUNLA_CORE_DIR, $name);
        }
        
        if( is_string($fileToRequire) ){
            if( file_exists($fileToRequire) )
                require $fileToRequire;
            return;
        }
        
        if( file_exists(sprintf('%s%s.php', MUNLA_CORE_DIR, $name)) ){
            require sprintf('%s%s.php', MUNLA_CORE_DIR, $name);
            return;
        }
        foreach(self::$types as $type){
            if( substr($name, 0 - strlen($type)) == $type ){
                $file = get::mvc_file($type, substr($name, 0, 0 - strlen($type)));
                if( $file === false ) return;
                require $file;
                return;
            }
        }
    }
    
    /**
     * Switches the current session over to another subdomain
     * - given that isolated subdomains has been configured.
     * 
     * @param string $to The subdomain to switch this session to.
     * 
     * @return void
     */
    public static function switchSessionSubdomain($to){
        if( !config::$isolated_subdomains ) return;
        $_SESSION[$to] = munla::$session;
        munla::$session['kill_munla_session'] = true;
    }
    
    /**
     * Starts the application.
     * 
     * @return void
     */
    public static function run(){
        self::$starttime = microtime(true);
        error_reporting(config::ERROR_LEVEL);
        if( isset(config::$session_cookie_domain) )
            ini_set('session.cookie_domain', config::$session_cookie_domain);
        
        if( class_exists('formHelper') )
            formHelper::fixArrays();
        
        if( class_exists('csrfHelper') )
            injector::register(array('csrfHelper', 'injector'));
        
        if( is::ssl() && isset(config::$https_domain) && !isset(config::$http_domain) ){
            if( is::existset($_GET, 'r_domain') )
                config::$http_domain = get::fulldomain($_GET['r_domain']);
            else
                config::$http_domain = get::fulldomain('www');
        }
        
        session_start();
        if( config::$isolated_subdomains ){
            // find the domain
            $domain = isset(config::$http_domain) ? config::$http_domain : get::fulldomain();
            
            // kill any subdomain sessions that have been transfered to another subdomain
            $existing = array_keys($_SESSION);
            foreach($existing as $d){
                if( !is_array($_SESSION[$d]) ) continue;
                if( array_key_exists('kill_munla_session', $_SESSION[$d]) && $_SESSION[$d]['kill_munla_session'] )
                    unset($_SESSION[$d]);
            }
            
            // initialize and setup the session for this subdomain
            if( !array_key_exists($domain, $_SESSION) ) $_SESSION[$domain] = array();
            munla::$session = &$_SESSION[$domain];
        }else munla::$session = &$_SESSION;
        
        if( class_exists('singleUseArray') ){
            if( !is::existset(munla::$session, 'MUNLA_SINGLE_USE') )
                munla::$singleUse = new singleUseArray();
            else
                munla::$singleUse = unserialize(munla::$session['MUNLA_SINGLE_USE']);
        }
        
        $route = get::route();
        if( is_array($route) && $route['controller'] == 'csrf' && $route['action'] == 'keepalive' && class_exists('csrfHelper') && is::existset($route, 'params') && count($route['params']) > 0 ){
            if( isset($_POST['token']) )
                echo csrfHelper::keepAlive($route['params'][0], $_POST['token']);
            exit();
        }
        
        if( class_exists('user') && is_subclass_of('user', 'userBase') ){
            if( !is::existset(munla::$session, 'MUNLA_USER') )
                munla::$session['MUNLA_USER'] = new userWrapper(new user());
        }
        
        injector::start();
        
        if( class_exists('app') && is_callable(array('app', 'setup')) )
            app::setup();
        
        if( !isset(munla::$user) && is::existset(munla::$session, 'MUNLA_USER') )
            munla::$user = &munla::$session['MUNLA_USER'];
        
        if( !is::ajax() ){
            $submittedForm = formHelper::process();
            if( isset($submittedForm) ) formHelper::process($submittedForm);
        }
        
        if( class_exists('app') && is_callable(array('app', 'start')) )
            $route = app::start($route);
        
        if( $route === null ) $route = array('controller' => 'index', 'action' => 'index', 'params' => null);
        
        $controller = get::controller($route['controller']);
        if( !isset($controller) && $route['controller'] != 'index' ){
            //push action to params, controller to action, and set controller to index and try again.
            if( $route['action'] != 'index' ){
                if( !isset($route['params']) ) $route['params'] = array();
                array_unshift($route['params'], $route['action']);
            }
            $route['action'] = $route['controller'];
            $route['controller'] = 'index';
            $controller = get::controller('index');
        }
        
        $view = null;
        if( isset($controller) ){
            $action = $controller->getAction(array($route['action'], $route['params']));
            if( isset($action) ){
                try{
                    $viewParams = call_user_func_array(array($controller, $action['action']), $action['params']);
                    //various things could happen here...
                    if( !isset($viewParams) ) $viewParams = array();
                    elseif( !is_array($viewParams) ) $viewParams = array($viewParams);
                    
                    $view = get::view($route, $controller, $viewParams);
                }catch(SSLException $e){
                    go::ssl(!is::ssl());
                }catch(PermissionException $e){
                    munla::$nohistory = true;
                    if( isset(munla::$user) && !munla::$user->is_logged_in() && munla::$user->getLoginView() )
                        $view = munla::$user->getLoginView();
                    else
                        $view = get::view('errors/generic', 'default', array('error_msg' => $e->getMessage()));
                }catch(Exception $e){
                    munla::$nohistory = true;
                    $view = get::view('errors/generic', 'default', array('error_msg' => $e->getMessage()));
                }
            }else $view = get::view($route, $controller);
        }else $view = get::view($route);
        
        if( $view != null ) $view->render();
        else throw new Exception('View not found!');
        
        if( class_exists('app', false) )
            munla::$nohistory = app::finish(munla::$nohistory);
        
        if( munla::$nohistory === false )
            munla::$session['lastpage'] = get::url();
        
        if( isset(munla::$singleUse) )
            munla::$session['MUNLA_SINGLE_USE'] = serialize(munla::$singleUse);
    }
    
}

/**
 * Register the autoloader.
 */
spl_autoload_register(array('munla', 'autoload'));
