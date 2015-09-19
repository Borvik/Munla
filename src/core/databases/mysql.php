<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * db_mysql
 * Provides functionality for connecting to a MySQL database.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class db_mysql extends db{
    
    private $last_error = null;
    protected function last_error(){
        return $this->last_error;
    }
    protected function init(array $details){
        if( !isset($this->db) ){
            try{
                $this->db = new PDO(sprintf('mysql:host=%s;dbname=%s', $details['server'], $details['db']), $details['user'], $details['password']);
                $this->connectDetails = $details;
            }catch(PDOException $e){
                $this->db = null;
                log::error('Unable to connect to database: '.$details['db']);
            }
        }
    }
    
    protected function close(){ $this->db = null; }
    
    protected function to_model($model_class, $results){
        if( $results === false ) return false;
        
        $ret = array();
        while($row = $results->fetch(PDO::FETCH_ASSOC))
            $ret[] = model::createNew($model_class, $row);
        
        if( count($ret) < 1 ) return false;
        return $ret;
    }
    
    protected function do_count($sql, array $params = null){
        $hasCount = (stripos($sql, 'select count(') === 0);
        if( !$hasCount ) $sql = sprintf('SELECT COUNT(*) FROM (%s) db_mysql_tbl_overlord', $sql);
        $result = $this->run_query($sql, $params);
        if( $result === false ) return false;
        $row = $result->fetch(PDO::FETCH_NUM);
        return $row[0];
    }
    
    protected function run_query($sql, array $params = null){
        $stmt = false;
        try{
            $stmt = $this->db->prepare($sql);
        }catch(PDOException $e){
            log::error('Error preparing query on database: '.$this->connectDetails['db'].' '.$e->getMessage());
        }
        if( !$stmt ) return false;
        
        if( !call_user_func(array($stmt, 'execute'), $params) ){
            $this->last_error = $stmt->errorInfo();
            log::$errorDetails = $this->last_error();
            log::error('Query executiong failed: '.$this->last_error[2]);
        }
        
        return $stmt;
    }
    
}