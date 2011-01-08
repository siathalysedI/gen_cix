<?php

/*
* phpCixGenerator : generic class for building a cix document
*
*/

class PHPCixGenerator {
  private $data = array();
  private $meta = array();
  public $xml;

  /*
  __construct : constructor
  @param $data: array of php data generated in the following format:
  $data = array(
  'functions' => array(),     # all global functions
  'classes'   => array(),     # all internally defined classes
  'variables' => array(),     # all global variables
  'constants' => array(),     # all global constants
  'meta'      => array(),     # meta data about the data, eg version, tag
  ):
  */
  
  function __construct() {
    
    if (!empty($data)) {
      $this->data = $data;
    }
  }
  /**
   * implementation of __set(), mostly for setting $this->set
   */
  
  function __set($name, $value) {
    
    if ($name == 'data' && !empty($value)) {
      $this->data = $value;
      
      if (isset($this->data['meta'])) {
        $this->meta = $this->data['meta'];
      }
    }
  }
  
  function gen_cix() {
    $file = $this->meta['file'];
    $cix = $this->_cix_skel($file, $this->meta);
    $top = $cix->getElementsByTagName('scope');
    $global = $top->item(0);
    
    if ($this->data['classes']) {
      foreach($this->data['classes'] as $class) {
        $classnode = $this->_new_domnode($cix, 'scope', $class['attr']);
        
        if (isset($class['functions'])) {
          foreach($class['functions'] as $method) {
            $fnode = $this->_gen_scope($method, $cix);
            $classnode->appendChild($fnode);
          }
        }
        
        if (array_key_exists('variables', $class) && is_array($class['variables'])) {
          foreach($class['variables'] as $var) {
            
            if (is_object($var)) { // XXX weird bug, $var should always be an array

              $var = (array)$var;
            }
            $vnode = $this->_new_domnode($cix, 'variable', $var);
            $classnode->appendChild($vnode);
          }
        }
        $global->appendChild($classnode);
      }
    }
    
    if ($this->data['functions']) {
      foreach($this->data['functions'] as $function) {
        $fnode = $this->_gen_scope($function, $cix);
        $global->appendChild($fnode);
      }
    }
    
    if ($this->data['variables']) {
      foreach($this->data['variables'] as $var) {
        $keys = FALSE;
        
        if (array_key_exists('keys', $var) && is_array($var['keys'])) {
          $keys = $var['keys'];
          $var = array(
            'name' => $var['name'],
            'citdl' => $var['citdl']
          );
        }
        $vnode = $this->_new_domnode($cix, 'variable', $var);
        
        if (is_array($keys)) {
          foreach($keys as $key) {
            $tmpnode = $this->_new_domnode($cix, 'variable', $key);
            $vnode->appendChild($tmpnode);
          }
        }
        $global->appendChild($vnode);
      }
    }
    return $cix->saveXML();
  }
  
  function _gen_scope($arr, &$cix) {
    $fnode = $this->_new_domnode($cix, 'scope', $arr);
    return $fnode;
  }
  
  function _cix_skel($file, $attr = array()) {
    $xml = new DOMDocument('1.0', 'utf-8');
    $xml->formatOutput = true;
    $root = $xml->CreateElement("codeintel");
    $attr = array_merge(array(
      'version' => '2.0'
    ) , $attr);
    $root = $this->_add_attributes($xml, $root, $attr);
    $file_attr = array(
      'lang' => 'PHP',
      'mtime' => time() ,
      'path' => $file,
    );
    $file = $this->_new_domnode($xml, 'file', $file_attr);
    $skel_array = array(
      'ilk' => 'blob',
      'lang' => 'PHP',
      'name' => '*',
      'id' => 'global'
    );
    $module = $this->_new_domnode($xml, 'scope', $skel_array);
    $file->appendChild($module);
    $root->appendChild($file);
    $xml->appendChild($root);
    return $xml;
  }

  /*
  * @name _new_domnode
  * @param $top: top-level DOMDocument
  * @param $name: name of the new node
  * @param $attr: optional array of attributes
  * @param text: optional text in the node
  * @return DOMNode $node
  */
  
  function _new_domnode(&$top, $name, $attr = false, $text = false) {
    $node = $top->createElement($name);
    
    if ($attr) {
      $node = $this->_add_attributes($top, $node, $attr);
    }
    
    if ($text) {
      $tmp_txt = $top->createTextNode($this->_format_txt($text));
      $node->appendChild($tmp_txt);
    }
    return $node;
  }

  /*
  * @name _add_attributes
  * @param & $xml: top-level DOMDocument node passed in by reference
  * @param $node: the node to add attributes to
  * @param $attr: assoc. array in key=>val pairs
  */
  
  function _add_attributes(&$xml, $node, $attr) {
    
    if (is_array($attr) && count($attr) > 0) {
      foreach($attr as $key => $val) {
        $tmp_attr = $xml->createAttribute($key);
        $tmp_attr->value = $this->_clean($val);
        $node->appendChild($tmp_attr);
      }
      return $node;
    } else {
      print_r($attr);
    }
  }
  
  function _clean($txt) {
    $txt = str_replace('*/', '', $txt);
    $txt = htmlentities(trim($txt));
    return $txt;
  }
}
