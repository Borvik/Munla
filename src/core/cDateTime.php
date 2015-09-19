<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * cDateTime
 * Provides a common class to help hold and display all date/time related values.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class cDateTime extends DateTime{
    
    const DATE_KIND = 1;
    const TIME_KIND = 2;
    const DATETIME_KIND = 3;
    const MONTH_KIND = 4;
    const WEEK_KIND = 5;
    const DATETIMELOCAL_KIND = 6;
    
    private static $ATTR = array('day', 'month', 'year', 'week', 'hour', 'minute', 'second', 'fraction', 'microsecond', 'meridiem', 'timezone');
    private static $DAY_KINDS = array(self::DATE_KIND, self::DATETIME_KIND, self::DATETIMELOCAL_KIND);
    private static $MONTH_KINDS = array(self::DATE_KIND, self::DATETIME_KIND, self::MONTH_KIND, self::DATETIMELOCAL_KIND);
    private static $YEAR_KINDS = array(self::DATE_KIND, self::DATETIME_KIND, self::MONTH_KIND, self::WEEK_KIND, self::DATETIMELOCAL_KIND);
    private static $TIME_KINDS = array(self::TIME_KIND, self::DATETIME_KIND, self::DATETIMELOCAL_KIND);
    
    /**
     * Flag that tells us what kind of date/time value we are dealing with.
     * 
     * 1 - Date
     * 2 - Time
     * 3 - DateTime
     * 4 - Month
     * 5 - Week
     */
    private $kind;
    
    public function __construct($datetime, $kind = 3){
        parent::__construct();
        if( !is_int($kind) || $kind < 1 || $kind > 6 )
            throw new InvalidArgumentException('Invalid datetime "kind" specified.');
        
        $this->setTimezone($datetime->getTimezone());
        $this->setDate($datetime->format('Y'), $datetime->format('n'), $datetime->format('j'));
        $this->setTime($datetime->format('G'), $datetime->format('i'), $datetime->format('s'));
        //$this->setTimestamp($datetime->getTimestamp());
        $this->kind = $kind;
    }
    
    public function removeTimezone(){
        $UTC = new DateTimeZone("UTC");
        $dt = new DateTime($this->format('Y-m-d H:i:s'), $UTC);
        return new cDateTime($dt, $this->kind);
    }
    
    public function lessThan(cDateTime $b){
        if( $this->kind != $b->kind ){
            $trace = debug_backtrace();
            trigger_error('Incompatible cDateTime kinds in lessThan() in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
            return null;
        }
        
        switch($this->kind){
            case self::DATE_KIND: return (date_create($this->format('Y-m-d')) < date_create($b->format('Y-m-d')));
            case self::TIME_KIND: return (date_create($this->format('H:i:s.u')) < date_create($b->format('H:i:s.u')));
            case self::DATETIMELOCAL_KIND: return (date_create($this->format('Y-m-d\\TH:i:s.u')) < date_create($b->format('Y-m-d\\TH:i:s.u')));$this->format('');
            case self::MONTH_KIND: return (date_create($this->format('Y-m')) < date_create($b->format('Y-m')));
            case self::WEEK_KIND: return (date_create($this->format('o-\WW')) < date_create($b->format('o-\WW')));
            case self::DATETIME_KIND: return (date_create($this->format('Y-m-d\\TH:i:s.uP')) < date_create($b->format('Y-m-d\\TH:i:s.uP')));
        }
        $trace = debug_backtrace();
        trigger_error('Unknown cDateTime kind in lessThan() in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
        return null;
    }
    
    public function __get($name){
        if( $name == 'kind' ) return $this->kind;
        
        if( !in_array($name, self::$ATTR) ){
            $trace = debug_backtrace();
            trigger_error('Undefined property via __get(): '.$name.' in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
            return null;
        }
        
        if( $name == 'day' && in_array($this->kind, self::$DAY_KINDS) )
            return (int)$this->format('j');
        if( $name == 'month' && in_array($this->kind, self::$MONTH_KINDS) )
            return (int)$this->format('n');
        if( $name == 'year' && in_array($this->kind, self::$YEAR_KINDS) )
            return (int)$this->format('Y');
        if( $name == 'week' && $this->kind == self::WEEK_KIND )
            return (int)$this->format('W');
        if( $name == 'hour' && in_array($this->kind, self::$TIME_KINDS) )
            return (int)$this->format('G');
        if( $name == 'minute' && in_array($this->kind, self::$TIME_KINDS) )
            return (int)$this->format('i');
        if( $name == 'second' && in_array($this->kind, self::$TIME_KINDS) )
            return (int)$this->format('s');
        if( ($name == 'fraction' || $name == 'microsecond') && in_array($this->kind, self::$TIME_KINDS) )
            return (int)$this->format('u');
        if( $name == 'meridiem' && in_array($this->kind, self::$TIME_KINDS) )
            return $this->format('a');
        if( $name == 'timezone' && in_array($this->kind, self::$TIME_KINDS) )
            return $this->getTimezone();
        
        $kind = 'Unknown';
        switch($this->kind){
            case self::DATE_KIND: $kind = 'Date'; break;
            case self::TIME_KIND: $kind = 'Time'; break;
            case self::DATETIME_KIND: $kind = 'DateTime'; break;
            case self::MONTH_KIND: $kind = 'Month'; break;
            case self::WEEK_KIND: $kind = 'Week'; break;
        }
        
        $trace = debug_backtrace();
        trigger_error('Property: '.$name.' not defined for DateTime kind '.$kind.' in '.$trace[0]['file'].' on line '.$trace[0]['line'], E_USER_NOTICE);
    }
    
    public function htmlspec(){
        switch($this->kind){
            case self::DATE_KIND: return $this->format('Y-m-d');
            case self::TIME_KIND: return $this->format('H:i:s.u');
            case self::DATETIMELOCAL_KIND: return $this->format('Y-m-d\\TH:i:s.u'); //$dt->format('Y-m-d\\TH:i'.($merged['isutc'] ? '\\Z' : ''));
            case self::MONTH_KIND: return $this->format('Y-m');
            case self::WEEK_KIND: return $this->format('o-\WW');
            case self::DATETIME_KIND: return $this->format('Y-m-d\\TH:i:s.uP');
        }
        return null;
    }
    
    public function format_us(){
        switch($this->kind){
            case self::DATE_KIND: return $this->format('n/j/Y');
            case self::TIME_KIND: return $this->format('g:i:s A');
            case self::DATETIMELOCAL_KIND: return $this->format('n/j/Y g:i:s A'); //$dt->format('Y-m-d\\TH:i'.($merged['isutc'] ? '\\Z' : ''));
            case self::MONTH_KIND: return $this->format('n/Y');
            case self::WEEK_KIND: return $this->format('\W\e\ek W, o');
            case self::DATETIME_KIND: return $this->format('n/j/Y H:i:sP');
        }
        return null;
    }
    
    public function __toString(){ return $this->htmlspec(); }
}