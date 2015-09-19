<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * config
 * Contains configuration settings for your application.
 * 
 * @package    Munla
 * @subpackage core\app replaceable
 * @author     Chris Kolkman
 * @version    1.0
 */
class config{
    
    const TITLE_DEFAULT = 'Default Title';
    
    public static $session_cookie_domain = null; //domain your session cookies should be used for
    public static $isolated_subdomains = false; //allows a shared subdomain session to keep the sessions for subdomains separate
    public static $csrf_form_secret = 'aZTlXhcA1755oEVmVOFl'; //secret key used to secure CSRF tokens
    
    public static $https_port = 443;
    public static $https_domain = null;
    
    public static $http_port = 80;
    public static $http_domain = null;
    
    public static $databases = array(); //list of databases, names as keys, details for the connection as sub-array
    
    /**
     * Specifies the default helpers for any given type.
     */
    public static $defaultHelpers = array(
                                          'controller' => array('html'),
                                          'template' => array('html', 'form')
                                          );
    
    /**
     * Enables a custom error handling function.
     * 
     * The following types of errors cannot be handled:
     *  E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, 
     *  E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT
     * 
     * This should be a valid callback function.  See php.net for
     * callback function format.
     * http://us.php.net/manual/en/language.pseudo-types.php#language.types.callback
     */
    public static $errorHandler = null;//array('errorController', 'errorHandler');
    
    /**
     * Specifies whether Munla should be in debug mode or not.
     * Debug mode collects more data during runtime and presents
     * it on errors or at the end of the page.
     * Possible values: html, js, true (same as html), false
     */
    const DEBUG_MODE = 'html';
    
    /**
     * Specifies the levels of errors that will be trapped
     * by the internal error handler.
     */
    const ERROR_LEVEL = 32766; //(E_ALL & ~E_ERROR);
    
}
