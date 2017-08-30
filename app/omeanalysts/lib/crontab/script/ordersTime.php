<?php
/**
 * �µ�ʱ���ѯ
 * @author Chris.Zhang
 * @access public
 * @copyright www.shopex.cn 2010.12.30
 */
class omeanalysts_crontab_script_ordersTime{

    public function orderTime(){
        @set_time_limit(0);
        $db = kernel::database();
        //$runtime = $this->runtime();
        //if($runtime == false) return;

        //base_kvstore::instance('omeanalysts_orderTime')->fetch('orderTime_time',$old_time);
        $old_time = app::get('omeanalysts')->getConf('old_time.gorderTime_time');//获取上次脚本执行时间
        $new_time = time();
        if(!$old_time){
            //base_kvstore::instance('omeanalysts_orderTime')->store('orderTime_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.gorderTime_time', $new_time);
            //$start_time = 'select min(createtime) as createtime from sdb_ome_orders';
            //$min_time = $db->selectrow($start_time);
            //$old_time = $min_time['createtime'];
            $old_time =$new_time - 86400;
            $info_sql = "truncate table sdb_omeanalysts_ordersTime";
            kernel::database()->exec($info_sql);
        }else {
            //base_kvstore::instance('omeanalysts_orderTime')->store('orderTime_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.gorderTime_time', $new_time);
        }
        $old_time = strtotime(date('Y-m-d',$old_time));
        $new_time = strtotime(date('Y-m-d',$new_time));
        $val = ($new_time-$old_time)/86400;

        if($val > 1){
            $te = $new_time;
            for($tim=0;$tim < $val;$tim++){

                $teo = $te-86400;
                $times[] = array('from_time'=>$teo,'to_time'=>$te);
                $te -= 86400;
            }
        }else{
            $times[0]=array('from_time'=>$old_time,'to_time'=>$new_time);
        }

        $orders = &app::get('ome')->model('orders');
        $ordersTime = &app::get('omeanalysts')->model('ordersTime');
        $shop = &app::get('ome')->model('shop');
        $shop_list = $shop->getList('shop_id');
        foreach($times as $time){
            //$times = array('from_time' => '1312560000','to_time' => '1312646400');
            $date = date('Y',$time['from_time']);
            $date0 = date('m',$time['from_time']);
            $date1 = date('d',$time['from_time']);
			
            if(($new_time-$old_time)/86400==1)
            {
	            foreach($shop_list as $v){
	                $k = 0;
	                $j = 1;
	                $row = array();
	                for($i=1;$i<=24;$i++){
	                    $date_time = mktime($k,0,0,$date0,$date1,$date);
	                    $date_time0 = mktime($j,0,0,$date0,$date1,$date);
	                    $sql0 = "select count(order_id) as num from sdb_ome_orders WHERE createtime >= ".$date_time." AND createtime < ".$date_time0." AND shop_id = '".$v['shop_id']."'";
	                    $data_info = $db->select($sql0);
	                    $order_time = date('H',$date_time);
	                    $hour = $this->method_code($order_time);
	                    $row[$hour] = $data_info;
	
	                    $k++;
	                    $j++;
	                }
	
	                $arr = array();
	                $arr['shop_id'] = $v['shop_id'];
	                $arr['dates'] = $time['from_time'];
	                foreach($row as $key => $value){
	                    	$arr[$key] = $value[0]['num'];
	                }
	                $ordersTime->insert($arr);
	            }
        	}
        }

    }

    function method_code($code=null,$return_type=true){
        $arr = $this->method_code_all();
         if (!empty($code) && $return_type){
            if (array_key_exists($code, $arr)) return $arr[$code];
            else return false;
        }elseif ($return_type == false){
            return $arr;
        }else return false;
    }

    function method_code_all(){
        $arr=array(
           '01'=>'h1',
           '02'=>'h2',
           '03'=>'h3',
           '04'=>'h4',
           '05'=>'h5',
           '06'=>'h6',
           '07'=>'h7',
           '08'=>'h8',
           '09'=>'h9',
           '10'=>'h10',
           '11'=>'h11',
           '12'=>'h12',
           '13'=>'h13',
           '14'=>'h14',
           '15'=>'h15',
           '16'=>'h16',
           '17'=>'h17',
           '18'=>'h18',
           '19'=>'h19',
           '20'=>'h20',
           '21'=>'h21',
           '22'=>'h22',
           '23'=>'h23',
           '00'=>'h24',
        );
        return $arr;
    }

    function runtime(){
        $timepart = date_parse(date('Y-m-d H:i:s',time()));
        return ($timepart['hour'] == '00' && $timepart['minute'] == '30') ? true : false;
    }

}
?>