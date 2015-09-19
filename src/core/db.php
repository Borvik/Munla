<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * db
 * Provides base functionality for a database connection.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
abstract class db{
    
    protected $db = null;
    protected $connectDetails = null;
    
    abstract protected function init(array $details);
    abstract protected function to_model($model_class, $results);
    abstract protected function do_count($sql, array $params = null);
    abstract protected function run_query($sql, array $params = null);
    abstract protected function close();
    abstract protected function last_error();
    
    /**
     * Autoload includes class files as they are needed.
     * 
     * @param string $name The name of the class to load.
     */
    public static function autoload($name){
        if( substr($name, 0, 3) == 'db_' && file_exists(sprintf('%s/databases/%s.php', MUNLA_CORE_DIR, substr($name, 3))) )
            require sprintf('%s/databases/%s.php', MUNLA_CORE_DIR, substr($name, 3));
    }
    
    /**
     * Get an instance of the database type specified in the details.
     * 
     * @param array $details The details used to create the connection.  Required keys may vary per database, but 'type' is required for all.
     * 
     * @return db An instance of the database class.
     */
    final public static function get(array $details){
        if( !array_key_exists('type', $details) )
            log::error('Database type missing from connection details.');
        
        $class = 'db_'.$details['type'];
        if( class_exists($class) ){
            unset($details['type']);
            $ret = new $class();
            $ret->init($details);
            return $ret;
        }else
            log::error(sprintf('Unknown database type: %s.', $details['type']));
    }
    
    /**
     * Returns the number of rows found for a given query.
     * 
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return int|false Returns the number of rows found, or a boolean FALSE on failure.
     */
    public function count($sql, $params = array()){
        if( !isset($this->db) ){
            if( error_reporting() !== 0 ) log::error('Error running database query. Database connection not initialized.');
            return false;
        }
        $args = func_get_args();
        if( count($args) > 1 && !is_array($params) ){
            array_shift($args);
            $params = $args;
        }
        return $this->do_count($sql, $params);
    }
    
    /**
     * Converts a set of query results to an object with the given class.
     * 
     * @param string $model The class of the model to give the results.
     * @param mixed $results The results list as returned from the raw query results.
     * 
     * @return array|false Returns an array of the given model, or boolean FALSE on failure or no results.
     */
    public function convert_to_model($model, $results){
        if( !class_exists($model) )
            log::error(sprintf('Class "%s" does not exist.', $model));
        return $this->to_model($model, $results);
    }
    
    /**
     * Performs a given query.
     * 
     * Used by other query methods, and also usefully for non-select queries.
     * 
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return mixed The query result.  Type differs based on database type.
     */
    public function raw_query($sql, $params = array()){
        if( !isset($this->db) ){
            if( error_reporting() !== 0 ) log::error('Error running database query. Database connection not initialized.');
            return false;
        }
        $args = func_get_args();
        if( count($args) > 1 && !is_array($params) ){
            array_shift($args);
            $params = $args;
        }
        return $this->run_query($sql, $params);
    }
    
    /**
     * Requests a single row/object from the data source.
     * 
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return stdClass The row/object as an object of type stdClass.
     */
    public function single($sql, $params = array()){
        $args = func_get_args();
        if( count($args) > 1 && !is_array($params) ){
            array_shift($args);
            $params = $args;
        }
        return $this->msingle('stdClass', $sql, $params);
    }
    
    /**
     * Requests a single row/object from the data source.
     * 
     * @param string $model The class of the model to give the results.
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return mixed The row/object as an object of type specified in $model or stdClass if $model does not exist.
     */
    public function msingle($model, $sql, $params = array()){
        $args = func_get_args();
        if( count($args) > 2 && !is_array($params) ){
            array_shift($args);
            array_shift($args);
            $params = $args;
        }
        $results = $this->mquery($model, $sql, $params);
        if( is_array($results) && count($results) == 1 ) return $results[0];
        return false;
    }
    
    /**
     * Requests the first row/object from the data source.
     * 
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return stdClass The row/object as an object of type stdClass.
     */
    public function first($sql, $params = array()){
        $args = func_get_args();
        if( count($args) > 1 && !is_array($params) ){
            array_shift($args);
            $params = $args;
        }
        return $this->mfirst('stdClass', $sql, $params);
    }
    
    /**
     * Requests the first row/object from the data source.
     * 
     * @param string $model The class of the model to give the results.
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return mixed The row/object as an object of type specified in $model or stdClass if $model does not exist.
     */
    public function mfirst($model, $sql, $params = array()){
        $args = func_get_args();
        if( count($args) > 2 && !is_array($params) ){
            array_shift($args);
            array_shift($args);
            $params = $args;
        }
        $results = $this->mquery($model, $sql, $params);
        if( is_array($results) && count($results) > 0 ) return $results[0];
        return false;
    }
    
    /**
     * Requests a set of rows/objects from the data source.
     * 
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return stdClass The row/object as an object of type stdClass.
     */
    public function query($sql, $params = array()){
        $args = func_get_args();
        if( count($args) > 1 && !is_array($params) ){
            array_shift($args);
            $params = $args;
        }
        return $this->mquery('stdClass', $sql, $params);
    }
    
    /**
     * Requests a set of rows/objects from the data source.
     * 
     * @param string $model The class of the model to give the results.
     * @param string $sql The query to run.
     * @param mixed $params Could be an array of scalar values for a parameterized query, or for some database types an array of additional options.  May also be scalar values for a parameterized query.
     * 
     * @return mixed The rows/objects as an object of type specified in $model or stdClass if $model does not exist.
     */
    public function mquery($model, $sql, $params = array()){
        if( $model === null ) $model = 'stdClass';
        elseif( $model != 'stdClass' && substr($model, -5) != 'Model' ) $model .= 'Model';
        if( !class_exists($model) ){
            log::warning(sprintf('Class "%s" does not exist, falling back to stdClass.', $model));
            $model = 'stdClass';
        }
        $args = func_get_args();
        if( count($args) > 2 && !is_array($params) ){
            array_shift($args);
            array_shift($args);
            $params = $args;
        }
        
        $results = $this->raw_query($sql, $params);
        return $this->to_model($model, $results);
    }
}

spl_autoload_register(array('db', 'autoload'), true, true);