<?php
/**
 * @物流任务列表操作
 *

 *
 * 版权所有 (C) 2003-2009 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址:http://www.shopex.cn/
 * -----------------------------------------------------------------
 * 您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 *
 * @category    acutal_task
 * @package    logisticsaccounts
 * @author     sunjing <sunjing@shopex.cn>
 */

class logisticsaccounts_finder_actual_task
{

     var $column_view = '操作';
     var $column_view_width = "140";
     function column_view($row){
         $find_id = $_GET['_finder']['finder_id'];
         $button='';

         

         if($_GET['flt']=='accounted'){
         if($row['status']=='0' || $row['status']=='1' || $row['status']=='4'){
        $button.= <<<EOF
<a target="dialog::{width:500,height:350,title:'导入'}" href="index.php?app=logisticsaccounts&ctl=admin_actual_task&act=import&action=import&_finder[finder_id]={$find_id}&finder_id={$find_id}&task_id={$row['task_id']}" icon="upload.gif" label="导入">
上传文件</a>&nbsp;&nbsp;
EOF;
         }
         if($row['status']!='3' && $row['status']!='2' && $row['status']!='5'){
         $button.= <<<EOF
<a href="index.php?app=logisticsaccounts&ctl=admin_actual&act=detail_actual&flt=accounted&task_id={$row['task_id']}" target="_blank">记账</a>&nbsp;&nbsp;
EOF;
         }
         if($row['status']=='3' || $row['status']=='2' || $row['status']=='5'){
        $button.= <<<EOF
<a href="index.php?app=logisticsaccounts&ctl=admin_actual&act=detail_actual&flt=view&task_id={$row['task_id']}" target="_blank">查看</a>&nbsp;&nbsp;
EOF;
         }
         }
         if($_GET['flt']=='confirm'){
        if($row['status']=='1' || $row['status']=='5' || $row['status']=='2'){
        $button.= <<<EOF
<a href="index.php?app=logisticsaccounts&ctl=admin_actual&act=detail_actual&flt=confirm&task_id={$row['task_id']}"  target="_blank">审核</a>&nbsp;&nbsp;
EOF;
         }
         if($row['status']=='2'){
$button.= <<<EOF
<a href="index.php?app=logisticsaccounts&ctl=admin_actual_task&act=close_actual&task_id={$row['task_id']}" >|关账</a>&nbsp;&nbsp;
EOF;

    }
    if($row['status']=='3'){
        $button.= <<<EOF
<a href="index.php?app=logisticsaccounts&ctl=admin_actual&act=detail_actual&flt=confirmview&task_id={$row['task_id']}" target="_blank">查看</a>&nbsp;&nbsp;
EOF;
         }
         }
         
    return $button;
}
    var $addon_cols = "actual_number,actual_total";
    var $column_account = "对账计数";
    var $column_account_width = "250";

    public function column_account($row)
    {

        return $row['_0_actual_number'].'/'.$row['_0_actual_total'];
    }

}

