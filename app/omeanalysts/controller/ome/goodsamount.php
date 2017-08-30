<?php
class omeanalysts_ctl_ome_goodsamount extends desktop_controller{
    
    var $name = "商品类目销售对比统计";

    public function goods_amount(){ //商品销售情况
        //修改日期范围
        
		if(empty($_POST['time_from']) or empty($_POST['time_to'])){
			$_POST['time_from'] = date("Y-m-d",(time() - 86400));
			$_POST['time_to'] = date("Y-m-d",(time() - 86400));
		}

        $_SESSION['data'] = $_POST;
        //商品销量统计crontab的手动调用
        //kernel::single('omeanalysts_crontab_script_goodsamount')->statistics();
        kernel::single('omeanalysts_ome_goodsamount')->set_params($_POST)->display();
    }


    public function chart_view() {
        $filter = $_GET;
		$time_from = strtotime($filter['time_from']);
		
		if($time_from == strtotime(date("Y-m-d",time()))){
			$time_from = ($time_from - 86400);
		}
		$last_time = date("Y-m-d",($time_from - 86400));
		$filter['time_from'] = date("Y-m-d",$time_from);
        $filter_new = array(
            'time_from' => $filter['time_from'],
            'time_to' => $filter['time_to'],
        );
		$fil = array(
	            'time_from' => $last_time,
	            'time_to' => date("Y-m-d",$time_from),
	    );
		$goods_name = array();
        $sold_out_rate = array();
        $goodsamountObj = $this->app->model('ome_goodsamount');
		
        $sales = $goodsamountObj->getlist('*',$filter);
		//echo '<pre>';
		//print_r($sales);
	//	$sales_old = $goodsamountObj->get_pre_sale($fil);
		$num_rate = 0;
		$top_ten_goods=array();
		$top_ten_rate=array();

        foreach($sales as $key=>$sale){
			/*
			$sales_old = $goodsamountObj->get_pre_sale($fil,$sale['goods_bn']);
			$num_rate =$sale['purchase_num']+$sale['allocation_num']+$sales_old['store'];
			//$g_rate = $sale['sales_num']/$num_rate;
			$g_rate = ($sale['sales_num']/$num_rate)*100;
			if($g_rate > 0.01){
				$g_rate = round($g_rate,2);
			}else{
				if($g_rate > 0){
					$g_rate = 0.01;
				}else{
					$g_rate = '0.00';
				}
			}
			*/
			
			
			
			$goods_name[] = '\''.$sale['name'].'\'';
			if($key<10){
				$top_ten_goods[] = '\''.$sale['name'].'\'';
				$top_ten_rate[] = str_replace('%','',$sale['sold_out_rate']);
				//$top_ten_rate[] = round($g_rate,8);
			}
			
		}
		$categories = implode(',',$top_ten_goods);
     	$volume = implode(',',$top_ten_rate);  
     	$this->pagedata['categories'] = '['.$categories.']';
		
     	$this->pagedata['data']='{
     		name: \'商品售罄率\',
     		data: ['.$volume.']
     	}';
		
		$this->display("ome/chart_type_percent_column.html");
        

    }

}