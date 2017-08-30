<?php
class omeanalysts_ctl_ome_goodsrma extends desktop_controller{
    

    public function goods_rma(){ //商品售后情况
     	 if(empty($_POST)){
			$_POST['time_from'] = date("Y-m-d",(time() - 86400));
			$_POST['time_to'] = date("Y-m-d",(time() - 86400));
		 }
	    //	kernel::single('omeanalysts_ome_goodsrank')->set_params($_POST)->display();
	    //商品售后量统计crontab的手动调用
	    //kernel::single('omeanalysts_crontab_script_goodsrma')->statistics();
	     kernel::single('omeanalysts_ome_goodsrma')->set_params($_POST)->display();
		
    }

	public function chart_view() {
        $filter = $_GET;
        if(isset($filter['report']) && $filter['report']=='month'){
            $time_from = strtotime($filter['time_from']);
			if($time_from == strtotime(date("Y-m-d"))){
				$time_from = ($time_from - 86400);
			}
			$last_time = date("Y-m-d",($time_from - 86400));
            $filter['time_from'] = date("Y-m-d",$time_from);
          //  $filter['time_to'] = date("Y-m-d",$filter['time_to']);
        }
        $filter = array(
            'time_from' => $filter['time_from'],
            'time_to' => $filter['time_to'],
        );
	
	/*	$fil = array(
	            'time_from' => $last_time,
	            'time_to' => date("Y-m-d",$time_from),
	    );
	*/
		$goods_name = array();
        $rma_rate = array();
        $goodsrmaObj = $this->app->model('ome_goodsrma');
		
      //  $rmas = $goodsrmaObj->get_sale($filter);
		$rmas = $goodsrmaObj->getlist('*',$filter);
	//	print_r($rmas);exit;
		//$sales_old = $goodsrmaObj->get_pre_sale($fil);
		//$rma_rate = 0;
		$top_ten_goods=array();
		$top_ten_rate=array();
        foreach($rmas as $key => $rma){
		/*	$num_rate =$rma['back_change_num'];
			$g_rate = $num_rate/$rma['sales_num'];
			$g_rate = $g_rate*100;
			if($g_rate>0.01){
				$g_rate = round($rate,2)."%";
			}else{
				if($g_rate>0){
					$g_rate = '0.01%';
				}else{
					$g_rate = '0.00%';
				}
			}
			$rma_rate[] = $g_rate;
			*/
			$goods_name[] = '\''.$rma['name'].'\'';
			if($key<10){
				$top_ten_goods[] = '\''.$rma['name'].'\'';
				if($rma['rma_rate']=='N/A'){
					$top_ten_rate[] = 0;
				}else{
					$top_ten_rate[] = str_replace('%','',$rma['rma_rate']);
				}
				
			//	$top_ten_rate[] = round($g_rate,8);
			}
		}
		
		
        $categories = implode(',',$top_ten_goods);
        $volume = implode(',',$top_ten_rate);       	         	
		        	
        $this->pagedata['categories'] = '['.$categories.']';
        $this->pagedata['data']='{
         		name: \'商品退换货率\',
         		data: ['.$volume.']
         	}';
		$this->display("ome/chart_type_percent_column.html");

    }


    
}