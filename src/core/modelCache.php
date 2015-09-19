<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * modelCache
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class modelCache extends secureProvider{
    
    protected $cache = null;
    
    protected function cache_keys(){ return array(); }
    
    public function &cache($value = null, &$result = null){
        if( !isset($this->cache) ) $this->cache = new ModelCacheStorage();
        $num_args = func_num_args();
        if( $num_args == 1 ){
            //store the cache
            if( is_array($value) ){
                foreach($value as $v){
                    if( !is::model($v) )
                        trigger_error('Array must contain only models');
                }
            }elseif( !is::model($value) )
                trigger_error(sprintf('Argument 1 must be an %s of %s "model"', (is_object($value) ? 'instance' : 'object'), (is_object($value) ? '' : 'class ')), E_USER_ERROR);
            $keys = $this->cache_keys();
            if( count($keys) > 0 ){
                $avalue = is_array($value) ? $value : array($value);
                foreach($avalue as $v){
                    foreach($keys as $key){
                        if( is_array($key) ){ //compound key
                            $ck = new StdClass;
                            foreach($key as $k){
                                if( !isset($v->{$k}) )
                                    throw new Exception(sprintf('Property %s does not exist.', $k));
                                $ck->{$k} = $v->{$k};
                            }
                            $this->cache->attach($ck, $v);
                        }else{
                            if( !isset($v->{$key}) )
                                throw new Exception(sprintf('Property %s does not exist.', $key));
                            $ck = new StdClass;
                            $ck->{$key} = $v->{$key};
                            $this->cache->attach($ck, $v);
                        }
                    }
                }
            }else
                trigger_error('Unable to cache model, cache_keys() does not containing any keys to index.', E_USER_ERROR);
            return $value;
        }elseif( $num_args == 2 ){
            //get the cache
            if( !is_array($value) )
                trigger_error('Argument 1 must be an array', E_USER_ERROR);
            
            $ck = new StdClass;
            foreach($value as $k => $v)
                $ck->{$k} = $v;
            
            $result = false;
            if( $this->cache->offsetExists($ck) ){
                $result = $this->cache[$ck]; $r = true;
                return $r;
            }
            return $result;
        }else
            trigger_error(sprintf('Incorrect number of arguments received. Expected 1 or 2, got %s.', $num_args));
    }
    
}

class ModelCacheStorage extends SplObjectStorage{
    public function getHash($o){
        $vars = get_object_vars($o);
        ksort($vars);
        return serialize($vars);
    }
}