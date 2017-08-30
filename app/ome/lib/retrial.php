<?php
/**
 +----------------------------------------------------------
 * 任务计划
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2014-05-28 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class ome_retrial
{
    /*------------------------------------------------------ */
    //-- 自动清除[180天 ||90天]复审日志
    /*------------------------------------------------------ */
    public function clean()
    {
    	$setting_is_monitor    = &app::get('ome')->getConf('ome.order.is_monitor');//是否开启价格监控
    	$setting_is_retrial    = &app::get('ome')->getConf('ome.order.is_retrial');//开启复审
    	$clean_time            = &app::get('ome')->getConf('ome.order.clean_day');//复审日志保留天数
    	$clean_time            = intval($clean_time) ? intval($clean_time) : 90;

    	if($setting_is_monitor=='true' || $setting_is_retrial=='true')
    	{
    		$time     = time();
    		$where    = " WHERE obj_type='orders@ome' AND operation='order_retrial@ome' AND operate_time<'".($time - $clean_time*24*60*60)."' ";
    		$del_sql  = "DELETE FROM ". DB_PREFIX ."ome_operation_log ".$where;
    		
    		kernel::database()->exec($del_sql);
    	}
    }
}