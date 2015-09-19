<?php
/**
 * This file is part of the Munla Framework - http://www.treorisoft.com.
 * (c) 2013 Chris Kolkman
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * db_solr
 * Provides functionality for connecting to a SOLR database.
 * The SOLR database needs to have the PHPSerializedResponseWriter configured with the name "phps".
 * 
 * @package    Munla
 * @subpackage core
 * @author     Chris Kolkman
 * @version    1.0
 */
class db_solr extends db{
    
    protected function last_error(){ return null; }
    
    protected function init(array $details){
        if( !isset($this->db) ){
            $required_fields = array('server', 'port', 'path');
            foreach($required_fields as $v)
                if( !array_key_exists($v, $details) )
                    log::error('Unable to connect to Solr database - missing required fields.');
            try{
                $this->db = sprintf('http%s://%s:%s%s', 
                    ((isset($details['secure']) && $details['secure']) ? 's' : ''),
                    $details['server'],
                    $details['port'],
                    $details['path']);
                @$this->ping();
                $this->connectDetails = $details;
            }catch(Exception $e){
                $this->db = null;
                log:error('Unable to connect to solr database on '.$details['server']);
            }
        }
    }
    
    protected function close(){ $this->db = null; }
    
    protected function to_model($model_class, $results){
        if( !isset($results) || $results === false || !isset($results['response']['docs']) ) return false;
        
        $ret = array();
        foreach($results['response']['docs'] as $product)
            $ret[] = model::createNew($model_class, $product);
        
        if( count($ret) < 1 ) return false;
        return $ret;
    }
    
    protected function do_count($sql, array $params = null){
        $params['start'] = 0;
        $params['rows'] = 1;
        $results = $this->run_query($sql, $params);
        if( $results && isset($results['response']['numFound']) ) return $results['response']['numFound'];
        return false;
    }
    
    protected function run_query($sql, array $params = null){
        $p = array('wt' => 'phps', 'q' => $sql);
        $p['start'] = get::array_def($params, 'start', 0);
        $p['rows'] = get::array_def($params, 'rows', 10);
        if( isset($params) && count($params) > 0 )
            $p = $p + $params;
        $debug = false;
        if( isset($p['debug_query']) && $p['debug_query'] ){
            unset($p['debug_query']);
            $debug = true;
        }
        $qs = http_build_query($p, null, '&');
        //$qs = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $qs); //kills arrays - solr doesn't handle arrays well, but does handle duplicate names
        $pat = array('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '/%5E/', '/%28/', '/%29/', '/%2A/', '/%3A/', '/%22/');
        $rep = array('=',                              '^',     '(',     ')',     '*',     ':',     '"');
        $qs = preg_replace($pat, $rep, $qs);
        $url = $this->db.'select?'.$qs;
        if( $debug ) log::debug($url);
        $results = $this->runSolrQuery($url);
        return $results;
    }
    
    private function runSolrQuery($url){
        $result = file_get_contents($url);
        if( $result === false ) throw new Exception('SOLR query failed to run.');
        return unserialize($result);
    }
    private function escapeQueryChars($str){
        $chars = array('+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/');
        return get::escaped_str($str, $chars);
    }
    private function unescapeQueryChars($str){
        $chars = array('+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/');
        return get::unsecaped_str($str, $chars);
    }
    private function ping(){
        $url = $this->db.'admin/ping?wt=phps&ts='.time();
        $result = $this->runSolrQuery($url);
    }
    
    public function phrase($value){ return sprintf('"%s"', get::escaped_str($value, array('"'))); }
    public function escape($value){ return $this->escapeQueryChars($value); }
    public function unescape($value){ return $this->unescapeQueryChars($value); }
    
    public function commit(){
        $urlCommit = $this->db.'update?wt=phps&stream.body=%3Ccommit%2F%3E';
        return $this->runSolrQuery($urlCommit);
    }
    public function optimize(){
        $urlOptimize = $this->db.'update?wt=phps&stream.body=%3Coptimize%2F%3E';
        return $this->runSolrQuery($urlOptimize);
    }
    public function commitAndOptimize(){
        $this->commit();
        $this->optimize();
    }
    public function rollback(){
        $url = $this->db.'update?stream.body=%3Crollback%2F%3E';
        return $this->runSolrQuery($url);
    }
    public function dih_full_import(){
        $url = $this->db.'dataimport?wt=phps&command=full-import';
        return $this->runSolrQuery($url);
    }
    public function dih_delta_import(){
        $url = $this->db.'dataimport?wt=phps&command=delta-import';
        return $this->runSolrQuery($url);
    }
    
    private function createField(&$xml, $name, $value){
        $field = $xml->createElement('field', $value);
        
        $fieldName = $xml->createAttribute('name');
        $fieldName->appendChild($xml->createTextNode($name));
        
        $field->appendChild($fieldName);
        return $field;
    }
    private function documentToXml($docs){
        if( !is_array($docs) ) $docs = array($docs);
        
        $xml = new DOMDocument();
        $root = $xml->createElement('doc');
        $xml->appendChild($root);
        foreach($docs as $d){
            if( is_array($d) ){
                foreach($d as $f => $v){
                    if( $f == '_version_' ) continue;
                    if( is_array($v) ){
                        foreach($v as $mv)
                            $root->appendChild($this->createField($xml, $f, $mv));
                    }else
                        $root->appendChild($this->createField($xml, $f, $v));
                }
            }elseif( is_object($d) ){
                $obj = null;
                if( get_class($d) == 'secureClass' && is_a($d->getObject(), 'model') ) $obj = $d->getObject();
                elseif( is_a($d, 'model') ) $obj = $d;
                
                if( $obj !== null ){
                    foreach($obj->getData() as $f => $v){
                        if( $f == '_version_' ) continue;
                        if( is_array($v) ){
                            foreach($v as $mv)
                                $root->appendChild($this->createField($xml, $f, $mv));
                        }else
                            $root->appendChild($this->createField($xml, $f, $v));
                    }
                }
            }
        }
        return $xml->saveXML($xml->firstChild);
    }
    public function update($document, $allowDups = false, $overwritePending = true, $overwriteCommitted = true){
        $raw = sprintf('<add allowDups="%s" overwritePending="%s" overwriteCommitted="%s">%s</add>',
            ($allowDups ? 'true' : 'false'),
            ($overwritePending ? 'true' : 'false'),
            ($overwriteCommitted ? 'true' : 'false'),
            $this->documentToXml($document));
        $url = $this->db.'update?wt=phps&stream.body='.urlencode($raw);
        return $this->runSolrQuery($url);
    }
    
    public function facets($sql, $field, $mincount = 1, array $params = array()){
        if( !is_array($field) ) $field = array($field);
        $params = array_merge($params, array('start' => 0, 'rows' => 1, 'facet' => 'true', 'facet.limit' => '-1', 'facet.mincount' => $mincount, 'facet.field' => $field));
        $result = $this->run_query($sql, $params);
        if( !isset($result['facet_counts']['facet_fields']) ) return array();
        return $result['facet_counts']['facet_fields'];
    }
    
    public function facetPivot($sql, $field, $mincount = 1, array $params = array()){
        if( is_array($field) ) $field = implode(',', $field);
        $params = array_merge($params, array('start' => 0, 'rows' => 1, 'facet' => 'true', 'facet.limit' => '-1', 'facet.pivot' => $field, 'facet.pivot.mincount' => $mincount));
        $result = $this->run_query($sql, $params);
        if( !isset($result['facet_counts']['facet_pivot'][$field]) ) return array();
        return $result['facet_counts']['facet_pivot'][$field];
    }
}
