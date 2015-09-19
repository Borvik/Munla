<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * cache
 * 
 * The cache class can be used to store and retrieve the result
 * of functions. Extending the class allows functions to be cached
 * automatically (precede all function names to be cached with
 * "cache_"). These cached results are only cached for the current
 * page request - though it can save time on complicated function calls
 * if they are called multiple times with the same parameters.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class cache extends extender{
    
    public static $cache = array();
    
    /**
     * Clears the cache for the specified function or the entire cache if a
     * function is not specified.
     * 
     * @param string $funcName
     *   The function name to clear from the cache.
     */
    public static function clear($funcName = null){
        if( $funcName === null ) self::$cache = array();
        if( array_key_exists($funcName, self::$cache) ) unset(self::$cache[$funcName]);
    }
    
    /**
     * Checks if the function result has been cached and returns
     * it via the referenced parameter.
     * 
     * @param string $name
     *   The name of the called function.
     * 
     * @param string $id
     *   The serialized function definition ([name] and paramerters to
     *   uniquely identify this call).
     * 
     * @param mixed &$result
     *   A variable to store the cached result in if it was cached.
     * 
     * @return boolean
     *   Returns a boolean true if the function was cached, false otherwise.
     */
    public static function &checkFunc($name, $id, &$result){
        $result = false;
        if( array_key_exists($name, self::$cache) && array_key_exists($id, self::$cache[$name]) ){
            $result = true;
            return self::$cache[$name][$id];
        }
        return $result;
    }
    
    /**
     * Stores a function result in the cache.
     * 
     * @param string $name
     *   The name of the function to cache.
     * 
     * @param string $id
     *   The serialized function definition ([name] and paramerters to
     *   uniquely identify this call).
     * 
     * @param mixed $result
     *   The result of the function call to store in the cache.
     * 
     * @param string $output
     *   Optional. Any output that was printed by the function.
     * 
     * @return mixed
     *   Returns the result as passed in $result.
     */
    public static function &func($name, $id, &$result, $output = ''){
        if( !array_key_exists($name, self::$cache) ) self::$cache[$name] = array();
        self::$cache[$name][$id] = array('result' => &$result, 'output' => $output);
        return self::$cache[$name][$id]['result'];
    }
    
    /**
     * Returns the cache file for the given key.
     * 
     * @param string $key The key for the cache file to get.
     * @return string The path to the cache file.
     */
    private static function get_cachefile($key){
        $skey = md5(serialize($key));
        return MUNLA_APP_DIR.sprintf('cache/%s.cache', $skey);
    }
    
    /**
     * Clears expired cache files.
     */
    public static function clear_cachefiles(){
        if( !$h = @opendir(MUNLA_APP_DIR.'cache') ) return;
        
        while( false !== ($f = readdir($h)) ){
            if( $f == '.' || $f == '..' ) continue;
            $file = MUNLA_APP_DIR.'cache/'.$f;
            
            $filetime = @filemtime($file);
            if( !$filetime ) continue;
            
            if( time() - $filetime >= 129600 )
                @unlink($file);
        }
        closedir($h);
    }
    
    /**
     * Checks if a given cache key has expired.
     * 
     * @param string $key The key for the cache file to get.
     * @return bool True if expired, false if not.
     */
    public static function file_is_expired($key){
        $file = self::get_cachefile($key);
        if( !@file_exists($file) ) return true;
        
        $filetime = @filemtime($file);
        if( !$filetime ) return true;
        
        $filehour = (int)date('G', $filetime);
        $nowhour = (int)date('G', time());
        if( $nowhour < $filehour ) return true;
        
        return (time() - $filetime >= 86400);
    }
    
    /**
     * Alias for is_expired.
     */
    public static function file_is_set($key){ return self::file_is_expired($key); }
    
    /**
     * Persists a given value to a key for the rest of the day.
     * 
     * @param string $key The key to persist.
     * @param mixed $value The value to persist.
     */
    public static function file_persist($key, $value){
        $file = self::get_cachefile($key);
        file_put_contents($file, serialize($value));
    }
    
    /**
     * Gets a value that has been persisted.
     * 
     * @param string $key The key for the cache file to get.
     * 
     * @throws Exception if the cache file has expired.
     * 
     * @return mixed The value that was persisted.
     */
    public static function file_get($key){
        $file = self::get_cachefile($key);
        if( file_exists($file) ){
            $data = unserialize(file_get_contents($file));
            if( !self::file_is_expired($key) )
                return $data;
            else
                self::clear_cachefiles();
        }
        throw new Exception('Cache file not found or expired');
    }
    
    /**
     * Magic method to enable automatic caching of member functions.
     */
    public function __call($name, $args){
        if( method_exists($this, 'cache_'.$name) || method_exists($this, 'ov_cache_'.$name) ){
            $fname = get_class($this).'->'.$name;
            $sname = base64_encode(gzcompress(serialize(array(get_class($this), spl_object_hash($this), $name, $args))));
            $value = cache::checkFunc($fname, $sname, $result);
            if( $result === true ){
                if( strlen($value['output']) > 0 )
                    echo $value['output'];
                return $value['result'];
            }
            ob_start();
            if( method_exists($this, 'ov_cache_'.$name) )
                $r = parent::__call('cache_'.$name, $args);
            else
                $r = call_user_func_array(array($this, 'cache_'.$name), $args);
            $output = ob_get_contents();
            ob_end_flush();
            return cache::func($fname, $sname, $r, $output);
        }
        return parent::__call($name, $args);
    }
    
    /**
     * Magic method to enable automatic caching of static functions.
     */
    public static function __callStatic($name, $args){
        $realName = '_'.$name;
        $class = get_called_class();
        if( method_exists($class, 'cache_'.$name) || method_exists($class, 'ov_cache_'.$name) ){
            $fname = $class.'::'.$name;
            $sname = base64_encode(gzcompress(serialize(array($class, $name, $args))));
            $value = cache::checkFunc($fname, $sname, $result);
            if( $result === true ){
                if( strlen($value['output']) > 0 )
                    echo $value['output'];
                return $value['result'];
            }
            ob_start();
            if( method_exists($class, 'ov_cache_'.$name) )
                $r = parent::__callStatic('cache_'.$name, $args);
            else
                $r = call_user_func_array(array($class, 'cache_'.$name), $args);
            $output = ob_get_contents();
            ob_end_flush();
            return cache::func($fname, $sname, $r, $output);
        }
        return parent::__callStatic($name, $args);
    }
}

