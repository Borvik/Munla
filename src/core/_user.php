<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * user
 * Provides a default user class for the most basic user functionality.
 * 
 * @package    Munla
 * @subpackage core\app replaceable
 * @author     Chris Kolkman
 * @version    1.0
 */
class user extends userBase{
    
    public function getLoginView(){}
    public function getUserModel(){}
    public function checkPermission($perm, $value = null){ return true; }
    public function login($username, $password){ return false; }
    
}