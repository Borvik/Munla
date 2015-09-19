<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * go
 * Contains functions that allow for navigation.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class go extends extender{
    
    /**
     * Redirects the user to the application's home page.
     * 
     * @param bool|null $ssl
     *   A boolean indicating whether the navigation should be to the https or http version
     *   of the home page.  May also be null, which will cause the page to stay in the same
     *   security context.
     */
    public static function home($ssl = null){
        if( !isset($ssl) || !is_bool($ssl) ) $ssl = is::ssl();
        go::url(get::url('index', $ssl));
    }
    
    /**
     * Redirects the user to the requested page under the specified
     * security context (https or http).
     * 
     * If the current context matches the requested context, then processing will
     * continue as normal.
     * 
     * @param bool $ssl
     *   A boolean indicating whether to switch to https or http.
     */
    public static function ssl($ssl = true){
        if( !is_bool($ssl) ) throw new InvalidArgumentException('Invalid argument passed to go::ssl().  Must be a boolean value.');
        if( is::ssl() == $ssl ) return;
        go::url(get::url($ssl));
    }
    
    /**
     * Attempts to redirect the user to the last page they
     * were on. If there is no history, then processing continues.
     * 
     * @return bool FALSE on failure.
     */
    public static function back(){
        if( is::existset(munla::$session, 'lastpage') && strlen(munla::$session['lastpage']) > 0 )
            return go::url(munla::$session['lastpage']);
        return false;
    }
    
    /**
     * Redirects the user to a new url.
     * 
     * All processing stops after this function call.
     * 
     * @param string $url The URL that the user should be redirected to.
     * 
     * @return void
     */
    public static function url($url, $response_code = null){
        if( isset(munla::$singleUse) )
            munla::$session['MUNLA_SINGLE_USE'] = serialize(munla::$singleUse);
        
        session_write_close();
        if( !headers_sent() ){
            if( isset($response_code) )
                header('Location: '.$url, true, $response_code);
            else
                header('Location: '.$url);
        }else{
            echo '<script type="text/javascript"> window.location.href="'.$url.'"; </script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
            echo '</noscript>';
        }
        exit();
    }
}