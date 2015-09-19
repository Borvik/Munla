<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$munladir = strtr(dirname(__FILE__), '\\', '/');
$appdir = strtr(dirname($_SERVER['SCRIPT_FILENAME']), '\\', '/');
if( substr($munladir, -1) != '/' ) $munladir .= '/';

if( defined('CUSTOM_APP_DIR') && strlen(constant('CUSTOM_APP_DIR')) > 0 )
    $appdir = strtr(constant('CUSTOM_APP_DIR'), '\\', '/');
if( substr($appdir, -1) != '/' ) $appdir .= '/';

define('MUNLA_CORE_DIR', $munladir);
if( !defined('MUNLA_APP_DIR') )
    define('MUNLA_APP_DIR', $appdir);

$pathRoot = '';
if( substr(MUNLA_APP_DIR, 0, strlen($_SERVER['DOCUMENT_ROOT'])) == $_SERVER['DOCUMENT_ROOT'] )
    $pathRoot = substr(MUNLA_APP_DIR, strlen($_SERVER['DOCUMENT_ROOT']));
define('MUNLA_WEB_ROOT', $pathRoot);

require MUNLA_CORE_DIR.'munla.php';
require MUNLA_CORE_DIR.'log.php';