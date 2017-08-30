<?php
/**
* 零销售产品分析
* @copyright shpoex.cn 2011.11.18
*/
class omeanalysts_crontab_script_zeroSale{

    /**
    * 按天统计零销售产品
    * @param 定时执行
    * @return 
    */
    public function statistics(){

        $time = time();
        $last_time = app::get('omeanalysts')->getConf('last_time.zeroSale');#上次脚本执行时间
        $db = kernel::database();
        if (!$last_time){
            // 取出最早的销售时间
            $sql = 'SELECT `sale_time` FROM `sdb_ome_sales` WHERE `sale_time` IS NOT NULL ORDER BY `sale_time` ASC';
            $last_sales = $db->selectrow($sql);
            $last_time = $last_sales['sale_time'];
            $this->start_time = $last_time;
        }
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
                    $to_days = date("j",time()-24*60*60);
                }
                for($day=$from_days;$day<=$to_days;$day++){
                    $dates = $year.'-'.$month.'-'.$day;
                     $this->_doStatistics($dates);
                }
                $from_days = 1;
            }
        }
        app::get('omeanalysts')->setConf('last_time.zeroSale', $time);
    }

    /**
    * 统计数据
    * @param $dates 日期:年-月-日,如2011-11-16
    * @return 
    */
    private function _doStatistics($dates){
        if ( empty($dates) ) return false;

        $db = kernel::database();
        $from_time = strtotime($dates.' 00:00:00');
        $to_time = strtotime($dates.' 23:59:59');
        $cur_months = date('Ym', strtotime($dates));
        $cur_day = intval(date('d', strtotime($dates)));
        $days_flag = str_pad('1',31,'1');#初始化天数标识,默认为1
        // 第一次统计,将最早销售时间之前的销售标识为0(也即未销售)
        if ($this->start_time){
            $start_day = intval(date('d', $this->start_time));
            for ($i=1;$i<=$start_day;$i++){
                $days_flag[($i-1)] = '0';
            }
            unset($this->start_time);
        }
        $zeroSaleModel = &app::get('omeanalysts')->model('zero_sale');

        $sql_counter = " SELECT count(*) ";
        $sql_list = " SELECT bp.branch_id,bp.product_id,p.bn,p.name,p.spec_info,g.type_id,g.brand_id ";
        $sql_base = sprintf(' FROM `sdb_ome_branch_product` AS bp,`sdb_ome_branch` AS b,`sdb_ome_products` AS p,`sdb_ome_goods` AS g WHERE bp.product_id=p.product_id AND bp.branch_id=b.branch_id AND b.attr=\'true\' AND bp.product_id NOT IN ( SELECT sales.product_id FROM ( SELECT distinct(p.product_id),s.branch_id,items.nums,items.price FROM `sdb_ome_sales_items` AS  items JOIN (`sdb_ome_sales` AS s,`sdb_ome_iostock` AS io,`sdb_ome_products` AS p) ON (s.sale_time>=\'%s\' AND s.sale_time<=\'%s\' AND s.iostock_bn=io.iostock_bn AND io.type_id=\'3\' AND items.sale_id=s.sale_id AND items.bn=p.bn) ) AS sales JOIN (`sdb_ome_branch_product` AS bp,`sdb_ome_branch` AS b) ON(bp.branch_id=b.branch_id AND b.attr=\'true\' AND sales.product_id=bp.product_id AND sales.branch_id=b.branch_id) ) AND p.goods_id=g.goods_id ',$from_time,$to_time);
        $sql = $sql_counter . $sql_base;
        $count = $db->count($sql);
        if ($count){
            $limit = 500;
            $pagecount = ceil($count/$limit);
            $serialnum_counter = array();
            for ($page=1;$page<=$pagecount;$page++){
                $lim = ($page-1) * $limit;
                $sql = $sql_list . $sql_base . " ORDER BY bp.`product_id` LIMIT " . $lim . "," . $limit;
                $data = $db->select($sql);
                if ($data){
                    foreach ($data as $value){
                        $branch_id = $value['branch_id'];
                        $product_id = $value['product_id'];
                        $sql = sprintf('SELECT bpsd_id,days FROM `sdb_omeanalysts_zero_sale` WHERE `product_id`=\'%s\' AND  `branch_id`=\'%s\' AND `months`=\'%s\'',$product_id,$branch_id,$cur_months);
                        $tmp = $db->selectrow($sql);
                        if (!$tmp['bpsd_id']){
                            //库存日报表主键ID
                            $sql = sprintf('SELECT id FROM `sdb_omeanalysts_branch_product_stock_detail` WHERE `product_id`=\'%s\' AND `branch_id`=\'%s\' AND `months`=\'%s\'',$product_id,$branch_id,$cur_months);
                            $bpsd_detail = $db->selectrow($sql);
                            if (!$bpsd_detail['id']) continue;

                            $days_flag[($cur_day-1)] = '0';
                            $spec_info = $value['spec_info'] ? '('.$value['spec_info'].')' : '';
                            $sdf = array(
                                'bpsd_id' => $bpsd_detail['id'],
                                'branch_id' => $branch_id,
                                'bn' => $value['bn'],
                                'product_id' => $value['product_id'],
                                'type_id' => $value['type_id'],
                                'name' => $value['name'].$spec_info,
                                'brand_id' => $value['brand_id'],
                                'months' => $cur_months,
                                'days' => $days_flag,
                            );
                            $zeroSaleModel->insert($sdf);
                        }else{
                            $update_days = $tmp['days'];
                            $update_days[($cur_day-1)] = '0';
                            $update_sdf = array('days'=>$update_days);
                            $zeroSaleModel->update($update_sdf,array('bpsd_id'=>$tmp['bpsd_id']));
                        }
                    }
                }
                $data = NULL;
            }
        }
    }

}