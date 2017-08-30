<?php
/**
 +----------------------------------------------------------
 * [跨境申报]安装任务
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2014-04-18 $
 * [Ecos!] (C)2003-2015 Shopex Inc.
 +----------------------------------------------------------
 */
class customs_task
{
    /**
     * 安装中执行
     *
     * @return void
     * @author
     **/
    public function post_install($options)
    {
        $dateline    = time();
        $password    = '123456';//md5
        
        #[插入]默认配置
    	$sql   = "INSERT INTO `sdb_customs_setting` SET company_id='1', company_code='12345678', company_name='商派软件有限公司', custom_type='1', 
    	          username='shopex', password='".$password."', dateline='".$dateline."', lastdate='".$dateline."'";
        kernel::database()->exec($sql);
        
        #[新增]订单异常类型
        $sql    = "SELECT type_id FROM `sdb_ome_abnormal_type` WHERE type_id=998";
        $row    = kernel::database()->selectrow($sql);
        if(empty($row))
        {
            $sql    = "INSERT INTO `sdb_ome_abnormal_type` SET type_id=998, type_name='跨境申报异常', disabled='false'";
            kernel::database()->exec($sql);
        }
    }
}