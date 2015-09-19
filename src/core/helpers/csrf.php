<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * csrf
 * Contains functions that help with CSRF token generation and validation.
 * 
 * @package    Munla
 * @subpackage core\helpers
 * @author     Chris Kolkman
 * @version    1.0
 */
class csrfHelper{
    
    private static $checkExpired = true; //prevents the expired check from happening multiple times
    private static $pageName = null; //stores the page name so it can be initialized and used for more than just csrf token tracking
    private static $timeout = 300; //csrf token timeout in seconds 300 = 5 minutes, 43200 = 12 hours
    
    /**
     * Injects csrf tokens into each form found on a webpage.
     * 
     * @param string $data The page being output to the browser.
     * 
     * @return string The modified page to output to the browser.
     */
    public static function injector($data){
        preg_match_all("/<form([^>]*)>(.*?)<\\/form>/is", $data, $matches, PREG_SET_ORDER);
        if( is_array($matches) ){
            self::removeExpiredTokens();
            //page name - used to invalidate other csrf tokens on the same page
            //if( self::$pageName === null )
            $name = self::generateName(); //uniqid("csrf_".md5(mt_rand()), true);
            foreach($matches as $m){
                if( preg_match("/<input\s+[^>]*(?:name\=[\"']csrf_token[\"'])[^>]*>/is", $m[2]) || strpos($m[1], 'nocsrf') !== false ) continue;
                $token = self::generate_token($name);
                $data = str_replace($m[0], "<form{$m[1]}><input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\" />{$m[2]}</form>", $data);
            }
        }
        preg_match_all("/<\\/body>/is", $data, $bmatches, PREG_SET_ORDER);
        if( is_array($bmatches) ){
            $kaTimeout = (self::$timeout - 30) * 1000;
            
            //generate keep alive javascript
            $js = sprintf('<script type="text/javascript"> function csrfKeepAlive(kurl, ktoken){ var config = {url:  kurl,data: "token="+ktoken}; var req; try{ req = new XMLHttpRequest(); }catch(e){ req = new ActiveXObject("Microsoft.XMLHTTP"); } var change = function(){ if( req.readyState == 4 && req.responseText != "false" && req.responseText.length > 0 ){ csrfKeepAliveToken = req.responseText; setTimeout(function(){ csrfKeepAlive(csrfKeepAliveUrl, csrfKeepAliveToken); }, %1$d); } }; req.open("POST", config.url, true); req.setRequestHeader("X_REQUESTED_WITH", "XMLHttpRequest"); req.setRequestHeader("Content-type", "application/x-www-form-urlencoded"); req.onreadystatechange = change; req.send(config.data); } var csrfKeepAliveUrl = "%2$s"; var csrfKeepAliveToken = "%3$s"; setTimeout(function(){ csrfKeepAlive(csrfKeepAliveUrl, csrfKeepAliveToken); }, %1$d); </script>',
                $kaTimeout, get::url('csrf/keepalive/'.self::generateName()), self::generate_keepAlive());
            
            foreach($bmatches as $m){
                $data = str_replace($m[0], $js.'</body>', $data);
            }
        }
        return $data;
    }
    
    /**
     * Generates a unique page name and stores it for later use.
     * 
     * @param string|null $newName A name to use for the page - if one hasn't already been created.
     * 
     * @return string A unique page name.
     */
    public static function generateName($newName = null){
        if( self::$pageName === null ){
            self::$pageName = ($newName !== null && is_string($newName)) ? $newName : uniqid('csrf_'.md5(mt_rand()), true);
        }
        return self::$pageName;
    }
    
    /**
     * Generates a CSRF token given a unique name and stores it in the session.
     * 
     * @param string $name A unique name for the form
     * @param bool $generateOnly (Optional) When TRUE will not store the token name in the session. Defaults to false.
     * 
     * @return string A unique token to validate against.
     */
    public static function generate_token($name, $generateOnly = false){
        $token = uniqid(md5(mt_rand()), true);
        $hash = sha1(config::$csrf_form_secret.'-'.$token);
        if( $generateOnly !== true )
            munla::$session['csrf_tokens'][$name] = time() + self::$timeout; //43200 = 12 hours
        return $name.'-'.$token.'-'.$hash;
    }
    
    public static function generate_page_token($data){
        $name = self::generateName();
        if( !isset(munla::$session['csrf_token_data'][$name]) )
            munla::$session['csrf_token_data'][$name] = array();
        munla::$session['csrf_token_data'][$name][] = $data;
    }
    
    /**
     * Generates a keep alive token.
     * 
     * @return string A unique keep alive token.
     */
    public static function generate_keepAlive(){
        $name = uniqid('keepAlive_'.md5(mt_rand()), true);
        return self::generate_token($name);
    }
    
    /**
     * Removes expired tokens.
     * 
     * @return void
     */
    private static function removeExpiredTokens(){
        if( !self::$checkExpired ) return;
        $now = time();
        if( is::existset(munla::$session, 'csrf_tokens') ){
            foreach(array_keys(munla::$session['csrf_tokens']) as $k){
                if( munla::$session['csrf_tokens'][$k] < $now )
                    self::removeToken($k);
            }
        }
        self::$checkExpired = false;
    }
    
    /**
     * Removes a token (page name) from the token list, and from the forms list.
     * 
     * @param string $name The page name of the shared tokens to remove.
     * 
     * @return void
     */
    private static function removeToken($name){
        if( is::existset(munla::$session, 'csrf_tokens') )
            unset(munla::$session['csrf_tokens'][$name]);
        if( is::existset(munla::$session, 'csrf_token_data') )
            unset(munla::$session['csrf_token_data'][$name]);
        if( is::existset(munla::$session, 'forms') )
            unset(munla::$session['forms'][$name]);
    }
    
    /**
     * Validates that a given CSRF page token is still valid.
     * 
     * @param string $pageName The page name token to validate.
     * 
     * @return bool
     */
    public static function pageValid($pageName){
        return (is::existset(munla::$session, 'csrf_tokens') && is::existset(munla::$session['csrf_tokens'], $pageName));
    }
    
    public static function dataValid($data){
        self::removeExpiredTokens();
        
        $is_valid = false;
        if( is::existset(munla::$session, 'csrf_token_data') ){
            foreach(munla::$session['csrf_token_data'] as $k => $v){
                if( in_array($data, $v) ){
                    $is_valid = true;
                    break;
                }
            }
        }
        return $is_valid;
    }
    
    /**
     * Validates a given CSRF token.
     * 
     * @param string $token The CSRF token to validate.
     * 
     * @return bool TRUE when the CSRF token is valid, FALSE otherwise.
     */
    public static function validate($token){
        self::removeExpiredTokens();
        
        //break down the csrf token into parts
        $parts = explode('-', $token);
        if( count($parts) != 3 ) return false;
        list($name, $token, $hash) = $parts;
        
        //confirm signature of token (generated here), and then that the name hasn't expired
        $result = false;
        if( self::pageValid($name) ){ //is::existset(munla::$session['csrf_tokens'], $name) ){
            $result = (sha1(config::$csrf_form_secret.'-'.$token) == $hash);
            self::removeToken($name);
        }
        return $result;
    }
    
    /**
     * Performs a csrf keep alive request and returns a new keep alive token.
     * 
     * If the keep alive fails the javascript should stop running so "false" is returned upon failure.
     * 
     * @param string $page  The csrf page tokens to keep alive.
     * @param string $token The keep alive token.
     * 
     * @param string Returns a new keep alive token upon success, or string false on failure.
     */
    public static function keepAlive($page, $token){
        if( substr($token, 0, 10) == 'keepAlive_' && self::validate($token) ){
            if( is::existset(munla::$session['csrf_tokens'], $page) )
                munla::$session['csrf_tokens'][$page] = time() + self::$timeout;
            return self::generate_keepAlive();
        }
        return 'false';
    }
    
    /**
     * Validates a given CSRF form submission.
     * 
     * @throws CSRFException when a POST form doesn't have a csrf field.
     * 
     * @return bool TRUE when CSRF token validates, false otherwise.
     */
    public static function validate_form(){
        self::removeExpiredTokens();
        
        if( count($_POST) ){
            if( !is::existset($_POST, 'csrf_token') )
                throw new CSRFException('No CSRF form fields were found.');
            
            return self::validate($_POST['csrf_token']);
        }
        return false;
    }
    
}

/**
 * Custom exception class for CSRF errors and warnings.
 */
class CSRFException extends Exception{
    
    public function __construct($message, $code = null, Exception $previous = null){
        parent::__construct($message, $code, $previous);
    }
    
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
    
}