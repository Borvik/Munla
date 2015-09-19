<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * db_mssql
 * Provides functionality for connecting to a MSSQL database.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class db_mssql extends db{
    
    protected function last_error(){ return sqlsrv_errors(); }
    
    protected function init(array $details){
        if( !isset($this->db) ){
            $server = $details['server'];
            $connInfo = array('UID' => $details['user'], 'PWD' => $details['password'], 'Database' => $details['db']);
            $this->db = sqlsrv_connect($server, $connInfo);
            if( $this->db === false ){
                log::error('Unable to connect to database: '.$details['db']);
                $this->db = null;
            }
            $this->connectDetails = $details;
        }
    }
    
    protected function close(){
        if( isset($this->db) ){
            $r = sqlsrv_close($this->db);
            if( $r ) $this->db = null;
        }
    }
    
    protected function to_model($model_class, $results){
        if( $results === false ) return false;
        
        $ret = array();
        while($row = sqlsrv_fetch_array($results, SQLSRV_FETCH_ASSOC))
            $ret[] = model::createNew($model_class, $row);
        
        if( count($ret) < 1 ) return false;
        return $ret;
    }
    
    protected function do_count($sql, array $params = null){
        $hasCount = (stripos($sql, 'select count(') === 0);
        if( !$hasCount ) $sql = sprintf('SELECT COUNT(*) FROM (%s) db_mssql_tbl_overlord', $sql);
        $result = $this->run_query($sql, $params);
        if( $result === false ) return false;
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC);
        return $row[0];
    }
    
    protected function run_query($sql, array $params = null){
        $res = sqlsrv_query($this->db, $sql, $params);
        if( $res === false && error_reporting() !== 0 ){
            log::$errorDetails = $this->last_error();
            log::error('Error running query on database: '. $this->connectDetails['db']);
        }
        return $res;
    }
    
    public function sp($sql, array &$params, $options = array()){
        if( !isset($this->db) ){
            if( error_reporting() !== 0 ) log::error('Error running database query. Database connection not initialized.');
            return false;
        }
        if( isset($options) && !is_array($options) ) $options = array();
        $result = sqlsrv_query($this->db, $sql, $params, $options);
        if( $result === false && error_reporting() !== 0 ){
            log::$errorDetails = $this->last_error();
            log::error('Error running query on database: '. $this->connectDetails['db']);
        }
        if( $result !== false ) sqlsrv_next_result($result);
        return $result;
    }
}