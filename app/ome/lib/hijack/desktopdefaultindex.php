<?php

/**
 * OME劫持desktop的default控制器的index方法
 * @copyright Copyright (c) 2011, shopex. inc
 * @author hjx
 * 
 */
class ome_hijack_desktopdefaultindex implements desktop_interface_controller_content
{
    function modify(&$html, &$obj){}

	function boot(){

        $app_info = cachecore::fetch('ome_hijack_version');
        if ($app_info === false) {
            $app_info = kernel::database()->select("SELECT app_id,local_ver FROM sdb_base_apps WHERE app_id IN('ectools','image','desktop')");
            cachecore::store('ome_hijack_version', $app_info, 86400*7);
        }
        foreach($app_info as $v){
            $localver_info[$v['app_id']] = $v['local_ver'];
        }
        
        $ectools_localver=app::get('ome')->getConf('ectools_localver');
        $image_localver=app::get('ome')->getConf('image_localver');
        $desktop_localver=app::get('ome')->getConf('desktop_localver');
        
        $func = new ome_func;
        
        if(empty($ectools_localver) || $ectools_localver != $localver_info['ectools']){
            //屏蔽ectools的管理界面
            $func->disable_menu('ectools');
            
            app::get('ome')->setConf('ectools_localver',$localver_info['ectools']);
        }
        
        if(empty($image_localver) || $image_localver != $localver_info['image']){
            //屏蔽image的管理界面
            $func->disable_menu('image');
            
            app::get('ome')->setConf('image_localver',$localver_info['image']);
        }
        
        /*
        if(empty($desktop_localver) || $desktop_localver != $localver_info['desktop']){
            //屏蔽desktop的管理界面
            kernel::database()->exec("UPDATE sdb_desktop_menus SET disabled='true',display='false' WHERE menu_type='adminpanel' AND menu_path IN ('app=desktop&ctl=data&act=index')");
            
            app::get('ome')->setConf('desktop_localver',$localver_info['desktop']);
        }
        */
    }
    
}