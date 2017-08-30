<?php

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class taoexlib_storager{

    function taoexlib_storager(){
        $this->base_url = kernel::base_url('full').'/';
        if(!defined(IE_FILE_STORAGER))define('IE_FILE_STORAGER','filesystem');
        $this->class_name = 'taoexlib_storage_'.IE_FILE_STORAGER;
        $this->worker = new $this->class_name;
        /*if(defined('HOST_MIRRORS')){
            $host_mirrors = preg_split('/[,\s]+/',constant('HOST_MIRRORS'));
            if(is_array($host_mirrors) && isset($host_mirrors[0])){
                $this->host_mirrors = &$host_mirrors;
                $this->host_mirrors_count = count($host_mirrors)-1;
            }
        }*/
    }

    function &parse($ident){
        $ret = array();
        if(!$ident){
            return false;
        }elseif(list($ret['url'],$ret['id'],$ret['storager']) = explode('|',$ident)){
            return $ret;
        }else{
            $ret['url'] = &$ident;
            return $ret;
        }
    }

    function save($content,$ext_name='csv'){
        if($id = $this->worker->save($content,$url,$ext_name)){
            return $url.'|'.$id.'|'.$this->class_name;
        }else{
            return false;
        }
    }
    
    function add($key,$content){
    	$tmp_content = $this->worker->get($key);
    	
    	if( $tmp_content ){
    		$content = $tmp_content . $content;
    	}
    	
    	return $this->worker->set($key, $content);
    }

    function test_get($key){
    	return $this->worker->test_get($key);
    }
    
    function remove($key){
    	return $this->worker->remove($key);
    }

    function get($key){
    	return $this->worker->get($key);
    }

}

