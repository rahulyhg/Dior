<?php
/**
* 库存状况综合分析
* @copyright shpoex.cn 2011.11.18
*/
class omeanalysts_crontab_script_bpStockDetail{

    /**
    * 按天统计库存状况
    * @param 定时执行
    * @return 
    */
    public function statistics(){

        $time = time();
        $last_time = app::get('omeanalysts')->getConf('last_time.stockDetail');#上次脚本执行时间
        $db = kernel::database();
        if (!$last_time){
            $last_time = $time-24*60*60;
        }

        if(($time-$last_time)<(24*3600-60)) return false;//执行时间间隔小于一天跳过

        $from_years = date('Y', $last_time);
        $from_months = date('m', $last_time);
        $from_days = date('j', $last_time);
        $to_years = date('Y',$time);
        $to_months = date('m',$time);

        for($year=$from_years;$year<=$to_years;$year++){
            for($month=$from_months;$month<=$to_months;$month++){
                if ( $year.$month < date('Ym') ){
                    $to_days = date('t', strtotime($year.'-'.$month));
                }else{
                    $to_days = date("j",$time-24*60*60);
                }
                for($day=$from_days;$day<=$to_days;$day++){
                    $dates = $year.'-'.$month.'-'.$day;
                    $this->_doStatistics($dates);
                }
                $from_days = 1;
            }
        }
        app::get('omeanalysts')->setConf('last_time.stockDetail', $time);
    }

    /**
    * 统计数据
    * @param $dates 日期:年-月-日,如2011-11-16
    * @return 
    */
    private function _doStatistics($dates){
        if ( empty($dates) ) return false;

        $db = kernel::database();
        $cur_months = date('Ym', strtotime($dates));
        $cur_day = date('j', strtotime($dates));
        $bpsdModel = &app::get('omeanalysts')->model('branch_product_stock_detail');

        $sql_counter = " SELECT count(*) ";
        $sql_list = " SELECT * ";
        $sql_base = sprintf(' FROM `sdb_ome_branch_product` ');
        $sql = $sql_counter . $sql_base;
        $count = $db->count($sql);
        if ($count){
            $limit = 500;
            $pagecount = ceil($count/$limit);
            $serialnum_counter = array();
            for ($page=1;$page<=$pagecount;$page++){
                $lim = ($page-1) * $limit;
                $sql = $sql_list . $sql_base . " ORDER BY `product_id` LIMIT " . $lim . "," . $limit;
                $data = $db->select($sql);
                if ($data){
                    foreach ($data as $value){
                        $branch_id = $value['branch_id'];
                        $product_id = $value['product_id'];
                        $store = $value['store'] > $value['store_freeze'] ? $value['store'] - $value['store_freeze'] : '0';
                        $sql = sprintf('SELECT id FROM `sdb_omeanalysts_branch_product_stock_detail` WHERE `product_id`=\'%s\' AND `branch_id`=\'%s\' AND `months`=\'%s\'',$product_id,$branch_id,$cur_months);
                        $tmp = $db->selectrow($sql);
                        if (!$tmp['id']){
                            $sdf = array(
                                'product_id' => $product_id,
                                'branch_id' => $branch_id,
                                'months' => $cur_months,
                                'day'.$cur_day => $store,
                            );
                            $bpsdModel->insert($sdf);
                        }else{
                            $update_sdf = array('day'.$cur_day=>$store);
                            $bpsdModel->update($update_sdf,array('id'=>$tmp['id']));
                        }
                    }
                }
                $data = NULL;
            }
        }
    }

}