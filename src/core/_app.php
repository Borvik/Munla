<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * app
 * Provides some function that allow user code at key points during the munla run process.
 * 
 * @package    Munla
 * @subpackage core\app replaceable
 * @author     Chris Kolkman
 * @version    1.0
 */
class app{
    
    /**
     * Is run after the route has been retrieved, and the user initialized.
     * 
     * @param array|null $route The route gathered from the URL.
     * 
     * @return array|null Returns a modified route.
     */
    public static function start(array $route = null){
        //do some things...
        return $route;
    }
    
    /**
     * Is run after the view has been rendered just before end of the script.
     * 
     * @param bool The current value of $nohistory: TRUE to prevent storing the url in history, FALSE otherwise.
     * 
     * @return bool TRUE to prevent storing the url in history, FALSE otherwise.
     */
    public static function finish($nohistory){
        return $nohistory;
    }
    
}