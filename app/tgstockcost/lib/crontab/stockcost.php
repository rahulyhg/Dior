<?php
/**
    * ShopEx licence
    *
    * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
    * @license  http://ecos.shopex.cn/ ShopEx License
    * @version osc---hanbingshu sanow@126.com
    * @date 2012-08-03
    * 定时脚本执行记录每天的库存成本
*/
class tgstockcost_crontab_stockcost
{
    function set()
    {
        if (!app::get('tgstockcost')->is_installed()) {
            return array('库存成本APP未安装！');
        }

        $ilog = array();

        $stockcost_last_run_time = app::get("ome")->getConf("tgstockcost_last_run_time");

        $stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
        // if(empty($stockcost_install_time)) return array('还没有进行成本设置,无需执行');
        // if(empty($stockcost_last_run_time)) $stockcost_last_run_time = $stockcost_install_time ;//没有执行时间  执行时间=APP安装时间

        $now = time();

        // 第一次执行，取一个月之前的
        $stockcost_last_run_time = $stockcost_last_run_time ? $stockcost_last_run_time : ($stockcost_install_time ? $stockcost_install_time : ($now-86400*10));
        
        $next_time = $stockcost_last_run_time+86400;
        $next_time = mktime(0,0,0,date('m',$next_time),date('d',$next_time),date('Y',$next_time));
        if ($next_time > $now) {
           return array('时间间隔小于一天,无需执行！'); 
        }

        $result = $this->process($stockcost_last_run_time,$msg);

        if ($result == false) return array($msg);

        app::get("ome")->setConf("tgstockcost_last_run_time",$now);

        return array('最后执行时间：'.date('Y-m-d H:i:s',$stockcost_last_run_time));
    }

    /**
     * 修复
     *
     * @return void
     * @author 
     **/
    public function repair($start_date,$end_date = null)
    {
        $start_time = $start_date ? strtotime($start_date) : app::get("ome")->getConf("tgstockcost_install_time");

        if ($end_date) $end_date = strtotime($end_date);

        $this->process($start_time,$msg,$end_date);
    }

    /**
     * 处理--初始化期初
     *
     * @return void
     * @author 
     **/
    private function process(&$start_time,&$msg,$end_time = null)
    {
        $branch_product_data = app::get("ome")->model("branch_product")->getList("branch_id,product_id,store,unit_cost,inventory_cost");
        if (!$branch_product_data) {
            $msg = '仓库中无货品！'; return false;
        }

        $dailystock = app::get("ome")->model("dailystock");

        $now = $end_time ? $end_time : time();
        do {
            // 取当天0点
            $nextday = mktime(23,59,59,date('m',$start_time),date('d',$start_time),date('Y',$start_time));
            if ($nextday > $now) break;

            foreach ((array) $branch_product_data as $bpk => $bpv) {
                $p_row = $dailystock->db->selectrow("select bn from sdb_ome_products where product_id=".intval($bpv['product_id']));
                if (!$p_row) continue;

                $cost_data_row = $this->get_last_iostock($bpv['branch_id'],$bpv['product_id'],$p_row['bn'],$nextday,$bpv);

                $insert_data = array();
                $insert_data['stock_date']     = date('Y-m-d',$nextday);
                $insert_data['branch_id']      = $bpv['branch_id'];
                $insert_data['product_id']     = $bpv['product_id'];
                $insert_data['product_bn']     = $p_row['bn'];
                $insert_data['stock_num']      = (int) $cost_data_row['balance_nums'];
                $insert_data['unit_cost']      = (float) $cost_data_row['now_unit_cost'];
                $insert_data['inventory_cost'] = (float) $cost_data_row['now_inventory_cost'];
                $insert_data['is_change']      = $this->is_change($bpv['branch_id'],$bpv['product_id'],$nextday,$cost_data_row['now_unit_cost']);


                $daily_filter = array(
                    'product_id' => $insert_data['product_id'],
                    'branch_id'  => $insert_data['branch_id'],
                    'stock_date' => $insert_data['stock_date'],
                );
                $dailystock->delete($daily_filter);
                // $ext_row = $dailystock->db->selectrow("select id from sdb_ome_dailystock where product_id=".intval($bpv['product_id'])." and branch_id=".intval($bpv['branch_id'])." and stock_date='".$insert_data['stock_date']."'");
                // if ($ext_row) {
                //     $insert_data['id'] = $ext_row['id'];
                // }

                $return_dailystock = $dailystock->save($insert_data);

                // $save_status = ($return_dailystock == false)?'fail':'success';
            }

            $start_time += 86400;                
        } while (true);

        return true;
    }

    /*计算时间前一天最后一次出入库流水记录*/
    function get_last_iostock($branch_id,$product_id,$bn,$last_end_time,$bpv = array())
    {
        $data = array();

        $stockcost_common_iostockrecord = kernel::single("tgstockcost_taog_iostockrecord");
        $io_type = $stockcost_common_iostockrecord->get_type_id(0);

        $iostock = app::get("ome")->model("iostock");

        $filter = array('bn' => $bn, 'branch_id' => $branch_id, 'create_time|sthan' => $last_end_time);
        $sql = 'SELECT iostock_id,create_time,now_unit_cost,now_num,now_inventory_cost,balance_nums,type_id FROM sdb_ome_iostock WHERE ' . $iostock->_filter($filter) . ' ORDER BY create_time DESC,balance_nums ASC';
        $data = $iostock->db->selectrow($sql);
    
        if($data && !in_array($data['type_id'],$io_type)){
            $sql = 'SELECT iostock_id,create_time,now_unit_cost,now_num,now_inventory_cost,balance_nums,type_id FROM sdb_ome_iostock WHERE ' . $iostock->_filter($filter) . ' ORDER BY create_time DESC,balance_nums DESC';
            
            $data = $iostock->db->selectrow($sql);
        }

        // 该日期之前没有出入库明细，之后又有出入库
        if (!$data) {

            $filter = array('bn' => $bn, 'branch_id' => $branch_id, 'create_time|than' => $last_end_time);

            $sql = 'SELECT iostock_id,create_time,now_unit_cost,now_num,now_inventory_cost,balance_nums,type_id,nums FROM sdb_ome_iostock WHERE ' . $iostock->_filter($filter) . ' ORDER BY create_time ASC,balance_nums DESC';
            $later_iostock = $iostock->db->selectrow($sql);

            if ($later_iostock) {
                if (in_array($later_iostock['type_id'],$io_type)){ 
                    $later_iostock['balance_nums'] += $later_iostock['nums'];
                } else{
                    $later_iostock['balance_nums'] -= $later_iostock['nums'];
                    $later_iostock['balance_nums'] = $later_iostock['balance_nums']>0 ? $later_iostock['balance_nums'] : 0;
                }

                $data = array(
                    'now_unit_cost' => $later_iostock['now_unit_cost'],
                    'now_num' => $later_iostock['balance_nums'],
                    'now_inventory_cost' => $later_iostock['now_unit_cost'] * $later_iostock['balance_nums'],
                    'balance_nums' => $later_iostock['balance_nums'],
                    'create_time' => $later_iostock['create_time'],
                );
            }
        }

        $stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
        // 如果当天的最后一条明细小于启用时间
        if ($data && $data['create_time'] < $stockcost_install_time) {
            $data['now_unit_cost'] = $bpv['unit_cost'];
            $data['now_inventory_cost'] = $data['balance_nums'] * $bpv['unit_cost'];
        }


        if (!$data && $bpv) {
            $data = array(
                'now_unit_cost' => $bpv['unit_cost'],
                'now_num' => $bpv['store'],
                'now_inventory_cost' => $bpv['inventory_cost'],
                'balance_nums' => $bpv['store'],
            );
        }

        return $data;

        // if (!$data) {
        //     $braProModel = app::get('ome')->model('branch_product');

        //     $filter = array('branch_id' => $branch_id, 'product_id' => $product_id);
        //     $sql = 'SELECT store,unit_cost,inventory_cost FROM sdb_ome_branch_product WHERE ' . $braProModel->_filter($filter);
        //     $brapro = $braProModel->db->selectrow($sql);
        //     if ($brapro) {
        //         $data = array(
        //             'now_unit_cost' => $brapro['unit_cost'],
        //             'now_num' => $brapro['store'],
        //             'now_inventory_cost' => $brapro['inventory_cost'],
        //         );
        //     }
        // }

        // $data = $iostock->db->select("select iostock_id,create_time,now_unit_cost,now_num,now_inventory_cost from sdb_ome_iostock where bn='".$bn."' and branch_id=".intval($branch_id)." and create_time<=".$last_end_time." order by create_time DESC limit 0,1");
        // $stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
        
        // if($data[0]['create_time']){
        //     $allData = $iostock->db->select("select iostock_id,create_time,now_unit_cost,now_num,now_inventory_cost from sdb_ome_iostock where bn='".$bn."' and branch_id=".intval($branch_id)." and create_time=".$data[0]['create_time']);
        // }
  
        // if($allData && count($allData) > 1){
        //     // 排序，取最后一条
        //     $tmpData = array();
        //     foreach ($allData as $d){
        //         $sec = substr($d['iostock_id'],0,10);
        //         $msec = str_pad(substr($d['iostock_id'],10),6,0,STR_PAD_LEFT);
        //         $id = $sec.$msec;
        //         $tmpData[$id] = $d;
        //     }
        //     unset($allData,$data);
        //     ksort($tmpData);
        //     $data[0] = array_pop($tmpData);
        // }

        // if($data[0]['create_time']<$stockcost_install_time){//如果最后一次出入库时间小于APP安装时间,说明安装后没有出入库行为 就取仓库货品表数据
        //     $branch_p_data = $iostock->db->selectrow("select store,unit_cost,inventory_cost from sdb_ome_branch_product where branch_id=".intval($branch_id)." and product_id=".intval($product_id));
        //     $data = array();
        //     $data['now_unit_cost'] = $branch_p_data['unit_cost'];
        //     $data['now_num'] = $branch_p_data['store'];
        //     $data['now_inventory_cost'] = $branch_p_data['inventory_cost'];
        //     return $data;

        // }else
        // return $data[0];
    }

    /*与上一条记录的单位成本是否有变化*/
    function is_change($branch_id,$product_id,$nextday,$unit_cost)
    {
        $iostock = app::get("ome")->model("iostock");

        $preday = date('Y-m-d',($nextday-86400));
        $sql = 'SELECT unit_cost FROM sdb_ome_dailystock WHERE product_id=' . intval($product_id) . ' AND branch_id=' . intval($branch_id) . ' AND stock_date="' . $preday . '"' ;

        $row = $iostock->db->selectrow($sql);

        return (0 == bccomp((float) $unit_cost, (float) $row['unit_cost'],3)) ? 0 : 1;
        // $dailystock_last_row = $iostock->db->select("select unit_cost from sdb_ome_dailystock where product_id=".intval($product_id)." and branch_id=".intval($branch_id)." order by id DESC limit 0,1");
        // if($unit_cost!=$dailystock_last_row[0]['unit_cost']) return 1;
        // else return 0;
    }
}