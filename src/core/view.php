<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * view
 * Provides code to render a view.
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
final class view{
    
    private $params = array();
    private $view = null;
    private $layout = null;
    
    private $lf = null; //layout file
    private $vf = null; //view file
    private $helpers = null; //helper array
    
    public function __construct($view, $layout, $params){
        $this->view = $view;
        $this->layout = $layout;
        $this->params = $params;
    }
    
    final public function render(){
        $this->lf = ($this->layout !== null) ? get::mvc_file('layout', $this->layout) : false;
        if( $this->lf === false && $this->layout != 'default' ) $this->lf = get::mvc_file('layout', 'default');
        
        $this->vf = ($this->view !== null) ? get::mvc_file('view', $this->view) : false;
        
        if( $this->lf === false ) throw new Exception('Unable to find layout: '.$this->layout);
        if( $this->vf === false ) throw new Exception('Unable to find view: '.$this->view);
        
        if( isset($this->params) && is_array($this->params) && is::assoc_array($this->params) )
            extract($this->params);
        
        $this->helpers = get::helpers('template');
        extract($this->helpers);
        
        ob_start();
        require $this->vf;
        $munla_view_data = ob_get_contents();
        ob_end_clean();
        
        if( !isset($page_title) ) $page_title = config::TITLE_DEFAULT;
        if( !isset($page_class) ) $page_class = preg_replace('[^a-zA-Z0-9-_]', '', str_replace('/', '-', $this->view));
        
        require $this->lf;
    }
}