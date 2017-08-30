<?php
/**
 * 根据传入的域名做初始化工作
 * 
 * @author hzjsq@msn.com
 * @version 1.0
 */
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);

kernel::single('base_shell_webproxy')->exec_command("update eccommon --ignore-download");

$eccommonObj = app::get('eccommon')->model('regions');
$region_grade = 3;
while($region_grade >1){
    $curr_region_arrs = $eccommonObj->getList('p_region_id',array('region_grade'=>$region_grade),0,-1);
    $tmp_p_region_ids = array();
    if($curr_region_arrs){
        foreach($curr_region_arrs as $curr_region){
            if(!in_array($curr_region['p_region_id'],$tmp_p_region_ids)){
                $tmp_p_region_ids[] = $curr_region['p_region_id'];
            }
        }
        $eccommonObj->db->exec("update sdb_eccommon_regions set haschild =1 where region_id in(".implode(',',$tmp_p_region_ids).")");
    }
    $region_grade--;
}
