<?php
/**
 * 清理订单日志
 * @author lijun
 * @package omeftp_service_log
 *
 */
class omemagento_log_clean{

    public function cleanlog(){
        $time = time();
        $clean_time = 60;
        $where = " WHERE `createtime`<'".($time-$clean_time*24*60*60)."' ";
       
        
        //从主表删除
        $del_sql = " DELETE FROM `sdb_omemagento_request_log` $where ";
        kernel::database()->exec($del_sql);
        kernel::database()->exec('OPTIMIZE TABLE `sdb_omemagento_request_log`');
        return true;
    }

}