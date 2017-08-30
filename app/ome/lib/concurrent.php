<?php
/**
 * 防止并发导致数据插入失败
 * @copyright Copyright (c) 2011, shopex. inc
 * @author sy
 * 
 */

class ome_concurrent{
    
	 /**
     * 自动清除同步日志
     * 每天检测将超2天的日志数据清除
     */
    public function clean(){
        
        $time = time();
        $clean_time = 2;
        $where = " WHERE `current_time`<'".($time-$clean_time*24*60*60)."' ";
        $del_sql = " DELETE FROM `sdb_ome_concurrent` $where ";
        kernel::database()->exec($del_sql);
        
        $del_sql = 'DELETE FROM `sdb_ome_concurrent` WHERE `current_time` IS NULL';
        kernel::database()->exec($del_sql);
    }
    
}