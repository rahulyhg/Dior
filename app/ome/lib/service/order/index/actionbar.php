<?php
/**
 * 订单列表finder上方按钮service
 * @author shiyao
 * @package ome_service_order_index
 * @copyright www.shopex.cn 2011.10.09
 *
 */
class ome_service_order_index_actionbar{
    
    /*
     * 获取按钮列表
     * @return array conf
     */
    public function getActionBar(){
        return  array(
	        		array(
	                    'label'=>'导出模板',
	                    'href'=>'index.php?app=ome&ctl=admin_order&act=exportTemplate',
	                    'target'=>'_blank'
	                ),
						array(
	                    'label'=>'生产AX文件',
	                    'submit'=>'index.php?app=ome&ctl=admin_order&act=sync_ax',
	                ),
        );
    }
    
}