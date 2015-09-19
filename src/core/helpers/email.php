<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * emailHelper
 * Contains functions that help with email sending.
 * 
 * @package    Munla
 * @subpackage core\helpers
 * @author     Chris Kolkman
 * @version    1.0
 */
class emailHelper extends extender{
    
    private $mail = null;
    public function clear_msg(){ $this->mail = null; }
    /**
     * Queues an email to a folder, so a separate process can email it at it's leisure.
     */
    public function queue($toOrTemplate){
        $args = func_get_args();
        $mail = $this->generateEmail($args);
        $mail['images'] = array_values($mail['images']);
        $skey = md5(uniqid(mt_rand(), true));
        $file = MUNLA_APP_DIR.sprintf('outbox/%s.eml', $skey);
        file_put_contents($file, json_encode($mail));
    }
    
    /**
     * Sends an email to a user.
     */
    public function send($toOrTemplate){
        $args = func_get_args();
        $headers = array();
        $mail = $this->generateEmail($args);
        $sep = sha1(date('r', time()));
        $headers[] = 'From: '.$mail['from'];
        $headers[] = 'To: '.implode(', ', $mail['to']);
        if( count($mail['cc']) > 0 )
            $headers[] = 'Cc: '.implode(', ', $mail['cc']);
        if( count($mail['bcc']) > 0 )
            $headers[] = 'Bcc: '.implode(', ', $mail['bcc']);
        
        $bsep = '--bnd_1_'.$sep;
        $headers[] = 'Content-Type: '.(count($mail['images']) > 0 ? sprintf('multipart/related; type="text/html"; boundary="%s"', $bsep) : 'text/html; charset=iso-8859-1');
        $eml = $mail['message'];
        if( count($mail['images']) > 0 ){
            $data = array();
            $data[] = '';
            $data[] = '--'.$bsep;
            $data[] = 'Content-type: text/html; charset=iso-8859-1';
            $data[] = '';
            $data[] = $eml;
            foreach($mail['images'] as $k => $img){
                $data[] = '--'.$bsep;
                $data[] = sprintf('Content-Type: %s; name="%s"', $img['size']['mime'], $img['name']);
                $data[] = 'Content-Transfer-Encoding: base64';
                $data[] = sprintf('Content-ID: <%s>', $k);
                $data[] = '';
                $data[] = trim($img['data']);
            }
            $data[] = '--'.$bsep.'--';
            $eml = implode("\r\n", $data);
        }
        return mail(implode(', ', $mail['to']), $mail['subject'], $eml, implode("\r\n", $headers));
    }
    
    private function generateEmail(array $args){
        if( count($args) < 1 || count($args) > 8 ) throw new Exception('Wrong parameter count');
        
        $ord = array(1 => 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth');
        $params = array('template' => null, 'to' => null, 'cc' => null, 'bcc' => null, 'subject' => null,
                            'message' => null, 'params' => null, 'prefix' => null, 'suffix' => null);
        $pkeys = array_keys($params); $argsC = $args;
        while(count($pkeys) > 0 && count($argsC) > 0){
            $param = array_shift($pkeys); $arg = array_shift($argsC);
            if( $param == 'template' ){
                if( !is_string($arg) || is::email($arg, true) )
                    $param = array_shift($pkeys);
                elseif( is_string($arg) )
                    $params['template'] = $arg;
                else
                    throw new InvalidArgumentException('First parameter expected to be a template or email address.');
            }
            if( in_array($param, array('to', 'cc', 'bcc')) ){
                if( isset($arg) && is_string($arg) && $arg != '' ){
                    if( $eml = is::email($arg, true) ){ $params[$param] = $eml; }
                    elseif( $param == 'to' )
                        throw new InvalidArgumentException(sprintf('%s parameter expected to be the recipients email address (1).', $this->ord($pkeys, $params['template'])));
                    else{
                        while($param != 'subject')
                            $param = array_shift($pkeys);
                    }
                }elseif( is_array($arg) ){
                    if( $eml = $this->getEmailAddressArray($arg) ) $params[$param] = $eml;
                    elseif( $param == 'to' )
                        throw new InvalidArgumentException(sprintf('%s parameter expected to be the recipients email address (2).', $this->ord($pkeys, $params['template'])));
                    else
                        throw new InvalidArgumentException(sprintf('%s parameter expected to be the subject or an email address.', $this->ord($pkeys, $params['template'])));
                }elseif( is::of_class($arg, 'emailAddressList') )
                    $params[$param] = $arg;
            }
            if( $param == 'subject' ){
                if( is_string($arg) ) $params[$param] = $arg;
                else throw new InvalidArgumentException(sprintf('%s parameter expected to be the subject.', $this->ord($pkeys, $params['template'])));
            }
            if( $params['template'] ){
                if( $param == 'message' ) $param = array_shift($pkeys);
                if( $param == 'params' ){
                    if( is_array($arg) ) $params[$param] = $arg;
                    else throw new InvalidArgumentException(sprintf('%s parameter expected to be an array of values for the template.', $this->ord($pkeys, $params['template'])));
                }elseif( in_array($param, array('prefix', 'suffix')) ){
                    if( !isset($arg) ) $params[$param] = '';
                    elseif( is_string($arg) ) $params[$param] = $arg;
                    else throw new InvalidArgumentException(sprintf('%s parameter expected to be a %s string.', $this->ord($pkeys, $params['template']). $param));
                }
            }elseif( $param == 'message' ){
                if( is_string($arg) ) $params[$param] = $arg;
                else throw new InvalidArgumentException(sprintf('%s parameter expected to be the %s string.', $this->ord($pkeys, $params['template']). $param));
            }
        }
        
        if( ($params['template'] && (!isset($params['subject']) || !isset($params['params']))) || (!isset($params['template']) && $params['subject'] && !isset($params['message']) && (!isset($this->mail) || !isset($this->mail['message']))) )
            throw new BadFunctionCallException('Unable to build a new message, missing parameters.');
        
        $emlKeys = array('to', 'cc', 'bcc'); $emailSet = false; $otherSet = false;
        foreach($params as $k => $v){
            if( isset($v) ){
                if( in_array($k, $emlKeys) ) $emailSet = true;
                else $otherSet = true;
            }
        }
        
        if( $emailSet && !$otherSet && !isset($this->mail) )
            throw new BadFunctionCallException('Unable to build a new message, only emails passed.');
        
        $mail = null;
        if( !isset($this->mail) ){
            //to, [cc], [bcc], [subject, message]
            //template, to, [cc], [bcc], [subject, params], [prefix], [suffix]
            $from = isset(config::$emailFrom) ? config::$emailFrom : 'noreply@'.get::domain();
            if( is_string($from) ){
                if( $eml = is::email($from, false) ) $from = (string)$eml;
                else throw new DomainException('From address is not a valid email - see config.1');
            }elseif( is_array($from) ){
                if( $eml = $this->getEmailAddressArray($from) ) $from = $eml;
                else throw new DomainException('From address is not a valid email - see config');
            }else throw new DomainException('From address is not a valid email - see config');
            
            $this->mail = array('from' => $from, 'images' => array());
            $mail = &$this->mail;
        }else{
            $mail = &$this->mail;
        }
        
        $mail['to'] = $this->emlAddrArray($params['to']);
        $mail['cc'] = (isset($params['cc']) ? $this->emlAddrArray($params['cc']) : null);
        $mail['bcc'] = (isset($params['bcc']) ? $this->emlAddrArray($params['bcc']) : null);
        if( isset($params['subject']) ) $mail['subject'] = $params['subject'];
        
        if( !isset($params['template']) && $params['message'] ){
            $mail['message'] = $params['message'];
            $mail['images'] = array();
        }elseif( $params['template'] ){
            $file = get::mvc_file('email', $params['template']);
            if( $file === false )
                throw new DomainException('Unable to find email template.');
            
            $mail['images'] = array();
            if( array_key_exists('images', $params['params']) && is_array($params['params']['images']) ){
                $kill = array(); $imgs = array();
                foreach($params['params']['images'] as $img => $path){
                    $un = uniqid('PDP-CID-');
                    $localpath = get::file_path($path);
                    if( $localpath === false ){ $kill[] = $img; continue; }
                    
                    $size = getimagesize($localpath);
                    if( $size === false ){ $kill[] = $img; continue; }
                    
                    $imgs[$un]['data'] = chunk_split(base64_encode(file_get_contents($localpath)));
                    $imgs[$un]['size'] = $size;
                    $imgs[$un]['name'] = basename($localpath);
                    $imgs[$un]['id'] = $un;
                    $params['params']['images'][$img] = $un;
                }
                foreach($kill as $k) unset($params['params']['images'][$k]);
                $mail['images'] = $imgs;
                $params['params']['images'] = new emailImageList($params['params']['images']);
                
                foreach($params['params']['images'] as $img => $un){
                    if( $params['prefix'] ) $params['prefix'] = str_replace('cid:'.$img, 'cid:'.$un, $params['prefix']);
                    if( $params['suffix'] ) $params['suffix'] = str_replace('cid:'.$img, 'cid:'.$un, $params['suffix']);
                }
            }
            $v = new emailView($file, $params['params']);
            $p = ($params['prefix']) ? $params['prefix'] : '';
            $s = ($params['suffix']) ? $params['suffix'] : '';
            $mail['message'] = $p.$v.$s;
            if( count($mail['images']) > 0 && array_key_exists('images', $params['params']) && is_object($params['params']['images']) ){
                $lst = $params['params']['images'];
                $imgs = $lst->toArray();
                foreach($lst->getKeys() as $k){
                    if( !$lst->wasAccessed($k) && array_key_exists($imgs[$k], $mail['images']) )
                        unset($mail['images'][$imgs[$k]]);
                }
            }
        }
        return $this->mail;
    }
    
    private function ord(array $p, $t){
        static $o = array(1 => 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth');
        $t = isset($t) ? 0 : 1;
        $i = 9 - count($p) - 1 - $t;
        return $o[$i];
    }
    
    private function emlAddrArray($addr){
        if( is_object($addr) && get_class($addr) == 'emailAddressList' ){
            $a = array();
            foreach($addr as $eml) $a[] = (string)$eml;
            return $a;
        }elseif( is_string($addr) ) return array($addr);
        elseif( is_array($addr) ) return $addr;
        return array();
    }
    private function getEmailAddressArray(array $addr, $emailOnly = false){
        if( !((isset($addr[1]) && is::email($addr[1], false)) || (isset($addr['email']) && is::email($addr['email'], false))) )
            return false;
        
        $name = (isset($addr[0]) ? $addr[0] : (isset($addr['name']) ? $addr['name'] : null));
        $eml = (isset($addr[1]) ? $addr[1] : (isset($addr['email']) ? $addr['email'] : null));
        if( isset($name) && !$emailOnly ) return sprintf('"%s" <%s>', str_replace('"', '', $name), $eml);
        return $eml;
    }
    
}

final class emailView{
    private $params = array();
    private $file = null;
    private $helpers = null; //helper array
    
    public function __construct($file, $params){
        $this->file = $file;
        $this->params = $params;
    }
    
    public function __toString(){
        if( isset($this->params) && is_array($this->params) && is::assoc_array($this->params) )
            extract($this->params);
        
        $this->helpers = get::helpers('template');
        if( count($this->helpers) > 0 )
            extract($this->helpers);
        
        ob_start();
        require $this->file;
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }
    
}

class emailImageList implements ArrayAccess, Countable, IteratorAggregate{
    
    private $list = array();
    private $accessed = array();
    
    public function __construct(array $list){
        $this->list = $list;
        $this->accessed = array();
    }
    
    public function count(){ return count($this->list); }
    public function offsetExists($offset){ return isset($this->list[$offset]); }
    public function offsetGet($offset){
        $this->accessed[$offset] = true;
        return isset($this->list[$offset]) ? $this->list[$offset] : null;
    }
    public function offsetSet($offset, $value){ $this->list[$offset] = $value; }
    public function offsetUnset($offset){ unset($this->list[$offset]); }
    public function getIterator(){ return new ArrayIterator($this->list); }
    
    public function resetAccessed(){ $this->accessed = array(); }
    public function wasAccessed($offset){
        if( isset($this->accessed[$offset]) ) return $this->accessed[$offset];
        return false;
    }
    
    public function used($offset){ $this->accessed[$offset] = true; }
    public function getKeys(){ return array_keys($this->list); }
    public function toArray(){ return $this->list; }
}
