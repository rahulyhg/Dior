<?php
/**
 +----------------------------------------------------------
 * 重建缓存API
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2014-08-26 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class ome_rpc_response_saasmanager_cache
{
    /*------------------------------------------------------ */
    //-- Mysql重建系统缓存
    /*------------------------------------------------------ */
    function re_mysql($data,& $apiObj)
    {
        $db         = kernel::database();
        $cacheObj   = new cachecore;
        $kvprefix   = base_kvstore::kvprefix();
        
        $sql        = "SELECT * FROM sdb_base_kvstore";
        $dataList   = $db->select($sql);
        foreach ($dataList as $key => $row)
        {
            $store          = array();
            $_interKey      = md5($kvprefix . $row['prefix'] . $row['key']);//$row['key'];
            
            $store['key']        = $_interKey;
            $store['o_key']      = $row['key'];
            
            $store['prefix']     = $row['prefix'];
            $store['value']      = unserialize($row['value']);
            $store['dateline']   = time();
            $store['ttl']        = $row['ttl'];
            
            $cacheObj->store($_interKey, $store, 864000);
        }
        
        $msg    = 'Mysql重建缓存成功';
        $apiObj->error_handle($msg);
    }
    
    /*------------------------------------------------------ */
    //-- MongoDB重建系统缓存
    /*------------------------------------------------------ */
    function re_mongodb($data,& $apiObj)
    {
        $obj    = kernel::single('base_kvstore_mongodb');
        $flag   = $obj->rebuild_memcache();
        
        $msg    = 'MongoDB重建缓存成功';
        if(!$flag)
        {
            $msg    = 'MongoDB重建缓存失败';
        }
        $apiObj->error_handle($msg);
    }
    
}
