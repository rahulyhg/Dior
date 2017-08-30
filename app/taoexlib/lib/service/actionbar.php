<?php
class taoexlib_service_actionbar {
	
	function getOmeanalystsDelivery(){
		// 新增app：接管csv导出任务
		return array(
					array(
					'label'=>'导出任务',
					'id'=>'export_task',
					'href'=>'index.php?app=taoexlib&ctl=ietask&act=export_task&e_app=omeanalysts&e_model=ome_delivery&task_name=快递费结算表',
					//index.php?app=ome&ctl=admin_order&act=index&_finder%5Bfinder_id%5D=d58620&action=export&finder_id=d58620
					'target'=>"dialog::{width:400,height:170,title:'导出'}"),
				);
	}
	
	function getActionBar(){
		// 新增app：接管csv导出任务
		return array(
					array(
					'label'=>'导出任务',
					'id'=>'export_task',
					'submit'=>'index.php?app=taoexlib&ctl=ietask&act=export_task&e_app=ome&e_model=orders&task_name='.base64_encode('订单'),
					//index.php?app=ome&ctl=admin_order&act=index&_finder%5Bfinder_id%5D=d58620&action=export&finder_id=d58620
					'target'=>"dialog::{width:400,height:170,title:'导出任务'}"),
				);
	}                    
}