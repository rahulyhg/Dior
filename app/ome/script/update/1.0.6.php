<?php
$sql = 'INSERT INTO sdb_ome_delivery_log (delivery_id,logi_id,logi_name,logi_no,create_time) 
    SELECT delivery_id,logi_id,logi_name,logi_no,create_time FROM sdb_ome_delivery WHERE logi_no is not null';
kernel::database()->exec($sql,true);

/*
 * 将带有BOM头北京的地区数据转成正确的格式，且删除新添加的北京地区数据
 */
$bom_area = chr(239).chr(187).chr(191)."北京";
$sql = "SELECT `region_id` FROM `sdb_ectools_regions` WHERE `local_name`='".$bom_area."'";
if ($old_area = kernel::database()->selectrow($sql)){
    
    //将带BOM头的北京数据转成正确格式
    $update_area_sql = " UPDATE `sdb_ectools_regions` SET `local_name`='北京' WHERE `region_id`='".$old_area['region_id']."' ";
    if (kernel::database()->exec($update_area_sql)){
        echo ' strip bom beijing success!';
    }
    
}else{
    echo ' area data right';
}

$desktop_user = kernel::single('desktop_user');
$desktop_user->user_id = 1; //强制将admin的绩效考核模块菜单不显示
$desktop_user->get_conf('fav_menus',$fav_menus);
if($fav_menus){
    foreach((array)$fav_menus as $key=>$menu){
        if($menu == 'performance'){
            unset($fav_menus[$key]);
        }
    }
    $desktop_user->set_conf('fav_menus',$fav_menus);
}
