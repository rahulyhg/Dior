<?php
class omeanalysts_ctl_ome_goodsrank extends desktop_controller{
	
	public function chart_view($filter=null) {
		$type=$_GET['type'];
        $filter = array(
            'time_from' => $_GET['time_from'],
            'time_to' => $_GET['time_to'],
        	'type_id' => $_GET['type_id'],
        	'order_status' => $_GET['order_status'],
        );
        $goodsObj = $this->app->model('ome_goodsrank');
         $goods_name = array();
         $sale_num = array();
         
         if ($type=='hotGoods'){
         	$data = $goodsObj->getlist('*',$filter,0,20,'sale_num desc');
         	foreach ($data as $val){
         		$goods_name[] = '\''.$val['name'].'\'';
         		$sale_num[] = $val['sale_num'];
         	}
         	$categories = implode(',',$goods_name);
         	$volume = implode(',',$sale_num);       	         	
		        	
         	$this->pagedata['categories'] = '['.$categories.']';
         	$this->pagedata['data']='{
         		name: \'热销排行\',
         		data: ['.$volume.']
         	}';
         }else if ($type=='dullGoods'){
         	$data = $goodsObj->getlist('*',$filter,0,20,'sale_num');
         	foreach ($data as $val){
         		$goods_name[] = '\''.$val['name'].'\'';
         		$sale_num[] = $val['sale_num'];
         	}
         	$categories = implode(',',$goods_name);
         	$volume = implode(',',$sale_num);
         	       	
         	$this->pagedata['categories'] = '['.$categories.']';
         	$this->pagedata['data']='{
         		name: \'滞销排行\',
         		data: ['.$volume.']
         	}';
         }
         $this->display("ome/chart_type_column.html");
	}
	
}
?>