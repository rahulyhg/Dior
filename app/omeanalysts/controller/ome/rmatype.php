<?php
class omeanalysts_ctl_ome_rmatype extends desktop_controller{
    

    public function rmatype(){ //售后类型分布统计
     	 if(empty($_POST)){
			$date_arr = $this->get_time_start();
			$_POST['time_from'] = $date_arr['now_zero_year'];
			$_POST['time_to'] = $date_arr['now_end_year'];
		 }
		 //售后类型分布统计crontab的手动调用
		 //kernel::single('omeanalysts_crontab_script_rmatype')->statistics();
	     kernel::single('omeanalysts_ome_rmatype')->set_params($_POST)->display();
		
    }
	
	function get_time_start(){
		$t   =   time();   
		$nowday   =   date('Y-m-d',mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t)));			//当天零点的时间戳 
		$nowmonth   =   date('Y-m-d',mktime(0,0,0,date("m",$t),1,date("Y",$t)));					//当月一号零点的时间戳
		$nextmonth =  date('Y-m-d',mktime(0,0,0,date("m",$t)+1,1,date("Y",$t)));					//下月一号零点时间戳
		$nowyear    =   date('Y-m-d',mktime(0,0,0,1,1,date("Y",$t)));								//当年一月一号零点时间戳
		$lastmonth =  date('Y-m-d',mktime(0,0,0,date("m",$t)-1,1,date("Y",$t)));					//上月一号零点时间戳
		$nowendmonth = date('Y-m-d',mktime(0,0,0,date("m",$t)+1,1,date("Y",$t))-24*60*60);
		$nowendyear = date('Y-m-d',mktime(0,0,0,12,31,date("Y",$t)));
		return array('now_zero_day' => $nowday,
					 'now_zero_month'=>$nowmonth,
					 'now_zero_year'=>$nowyear,
					 'next_zero_month'=>$nextmonth,
					 'last_zero_month'=>$lastmonth,
					 'now_end_month'=>$nowendmonth,
					 'now_end_year'=>$nowendyear
		);
	}

	public function chart_view() {
        $filter = $_GET;
        if(isset($filter['report']) && $filter['report']=='month'){
            $time_from = strtotime($filter['time_from']);
			if($time_from == strtotime(date("Y-m-d"),time())){
				$time_from = ($time_from - 86400);
			}
			$last_time = date("Y-m-d",($time_from - 86400));
            $filter['time_from'] = date("Y-m-d",$time_from);
            $filter['time_to'] = date("Y-m-d",$filter['time_to']);
        }
		
        $filter = array(
            'time_from' => $filter['time_from'],
            'time_to' => $filter['time_to'],
			'type_id' => $filter['type_id'],
        );
		$fil = array(
	            'time_from' => $last_time,
	            'time_to' => date("Y-m-d",$time_from),
	    );

        $rmatypeObj = $this->app->model('ome_rmatype');
		
        $rmatypes = $rmatypeObj->get_rmatype($filter);
			
		$rma_type_name = array();
        $rma_num = array();
        foreach($rmatypes as $key => $rmatype){
		//	$sales_old = $goodsrmaObj->get_pre_sale($filter,$rma['goods_bn']);
			$rma_type_name[] = '\''.$rmatype['name'].'\'';
			$rma_num[] = $rmatype['num'] ? $rmatype['num'] : 0;
			
		}

        $categories = implode(',',$rma_type_name);
        $volume = implode(',',$rma_num);       	         	
		        	
        $this->pagedata['categories'] = '['.$categories.']';
        $this->pagedata['data']='{
         		name: \'售后类型分布统计\',
         		data: ['.$volume.']
         	}';

	//	print_r($this->pagedata);
		$this->display("ordersPrice/chart_type_column.html");

    }


    
}