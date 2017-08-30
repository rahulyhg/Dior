<?php

/*
 * @package base
 * @copyright Copyright (c) 2010, shopex. inc
 * @author edwin.lzh@gmail.com
 * @license 
 */
class base_storage_ecaesystem implements base_interface_storager
{
    private $_tmpfiles = array();

    function __construct()
    {
        //todo;
    }//End Function

    public function save($file, &$url, $type, $addons, $ext_name="")
    {
        if($type=='public'){
            $group_id = 'public'; 
        }elseif($type=='private'){
            $group_id = 'private'; 
        }else{
            $group_id = 'images'; 
        }
        $filename = basename($file) . '.' . $ext_name;
        $ident = ecae_file_save($group_id, $file, array('name'=>$filename));
        if($ident){
            $url = ecae_file_url($ident);
            return $ident;
        }else{
            return false;
        }
    }//End Function

    public function replace($file, $id)
    {
        return ecae_file_replace($id, $file);
    }//End Function


    public function remove($id)
    {
        if($id){
            return ecae_file_delete($id);
        }else{
            return false;
        }
    }//End Function

    public function getFile($id, $type)
    {
        $tmpfile = tempnam('/tmp', 'ecaesystem');
        array_push($this->_tmpfiles, $tmpfile);
        if($id && ecae_file_fetch($id, $tmpfile)){
            return $tmpfile;
        }else{
            return false;
        }
    }//End Function

    function __destruct() 
    {
        foreach($this->_tmpfiles AS $tmpfile){
            @unlink($tmpfile);
        }//todo unlink tmpfiles;
    }//End Function

}//End Class
