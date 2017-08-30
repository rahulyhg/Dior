<?php
/**
 +----------------------------------------------------------
 * [任务计划]跨境申报
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2015-04-18 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class customs_orders
{
    /*------------------------------------------------------ */
    //-- 自动清除[180天 ||90天]复审日志
    /*------------------------------------------------------ */
    public function clean()
    {
        $clean_time            = 180;//日志保留天数
        
        $time     = time();
        $where    = " WHERE obj_type='orders@customs' AND operate_time<'".($time - $clean_time*24*60*60)."' ";
        $del_sql  = "DELETE FROM ". DB_PREFIX ."ome_operation_log ".$where;
        
        kernel::database()->exec($del_sql);
    }
}