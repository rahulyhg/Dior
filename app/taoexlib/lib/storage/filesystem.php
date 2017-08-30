<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class taoexlib_storage_filesystem{

    function taoexlib_storage_filesystem(){
        $this->memcache=new base_kvstore_filesystem('ie_store');
        //$host_mirrors = preg_split('/[,\s]+/',constant('IE_STORAGE_MEMCACHED'));
        //if(is_array($host_mirrors) && isset($host_mirrors[0])){
        //    foreach($host_mirrors as $k =>$v){
        //        list($host,$port) = explode(":",$v);
        //        $this->memcache->addServer($host,$port);
        //    }
        //}
    }

    function save($content,&$url,$ext_name){
        $id = $this->_get_ident($ext_name);
        //$url = IE_STORAGE_HOST . $id;
	//echo $content;
        if($this->memcache->store($id,$content)){
            
            $url = $this->getFile($id, 'public');
	    return $id;
        }else{
            return false;
        } 
    }

    function replace($content,$id){
        if($this->memcache->store($id,$content)){
            return $id;
        }else{
            return false;
        }
    }

    function _get_ident($ext_name){    
        return $this->_ident().'.'.$ext_name;
    }


    function remove($id){
        if($id){
            return $this->memcache->delete($id,10);
        }else{
            return true;
        }
    }

    function _ident(){
        return '/'.md5(microtime().base_certificate::get()).'/'.rand(0,time());
    }

    function getFile($id,$type){
        if($type=='public'){
            $f_dir = DATA_DIR.'/public'; 
        }else{
            $f_dir = DATA_DIR.'/private'; 
        }
	$content = null;
	$this->memcache->fetch($id, $content);
        $tmpfile = tempnam($f_dir, 'export_');
        if($id && file_put_contents($tmpfile,$content)){
	    $_POST['_f_type'] = 'public';
	    $file= array('name' => 'xx.csv', 'tmp_name' => $tmpfile);
	    $obj = new base_storager();
	    $url = $obj->save_upload($file,'file','',$msg);
	    
	    $url = sprintf('index.php?app=taoexlib&ctl=ietask&act=download&id=%s', $url);
		
            return $url; //$obj->getUrl($url);
        }else{
            return true;
        }
    }
    
    function test_get($id){
    	return $this->memcache->fetch($id);
    }
}
