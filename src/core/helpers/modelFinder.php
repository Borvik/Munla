<?php

class modelFinderHelper{
    
    /**
     * Attempts to find a model in the array given the keys/values specified.
     * 
     * @param array $array The array to search.
     * @param array $kv The keys and values to match.
     * @param bool $returnKey Boolean value to determine whether to return the object or the array key.
     * 
     * @return mixed Returns the object (or array key) on success, or FALSE on failure.
     */
    public function &find(array &$array, array $kv, $returnKey = false){
        foreach($array as $idx => &$obj){
            $is_match = true;
            foreach($kv as $k => $v){
                if( $obj->{$k} != $v ){
                    $is_match = false;
                    break;
                }
            }
            if( $is_match ){
                if( $returnKey ) return $idx;
                return $obj;
            }
        }
        $mret = false;
        return $mret;
    }
}