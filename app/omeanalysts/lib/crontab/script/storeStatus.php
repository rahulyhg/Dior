<?php
/**
* 库存状况综合分析
* @copyright shpoex.cn 2011.11.18
*/
class omeanalysts_crontab_script_storeStatus{

    /**
    * 按天统计库存状况
    * @param 定时执行
    * @return 
    */
    public function statistics(){

        $time = time();
        $last_time = app::get('omeanalysts')->getConf('last_time.storeStatus');#上次脚本执行时间

        if(($time-$last_time)<(24*3600-60)) return false;//执行时间间隔小于一天跳过

        $db = kernel::database();
        if (!$last_time){
            // 取出最早的销售时间
            $sql = 'SELECT `sale_time` FROM `sdb_ome_sales` WHERE `sale_time` IS NOT NULL ORDER BY `sale_time` ASC';
            $last_sales = $db->selectrow($sql);
            $last_time = $last_sales['sale_time'];
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
                    $to_days = date("j",$time-24*60*60);
                }
                for($day=$from_days;$day<=$to_days;$day++){
                    $dates = $year.'-'.$month.'-'.$day;
                    $this->_doStatistics($dates);
                }
                $from_days = 1;
            }
        }
        app::get('omeanalysts')->setConf('last_time.storeStatus', $time);
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
        $sales_time = strtotime($dates);
        $sale_productsModel = &app::get('omeanalysts')->model('sale_products');

        // 销售商品
        $sql = sprintf(' SELECT sales.* FROM ( SELECT distinct(p.product_id),s.branch_id,items.nums,items.price,s.sale_time FROM `sdb_ome_sales_items` AS items JOIN (`sdb_ome_sales` AS s,`sdb_ome_iostock` AS io,`sdb_ome_products` AS p) ON (s.sale_time>=\'%s\' AND s.sale_time<=\'%s\' AND s.iostock_bn=io.iostock_bn AND io.type_id=\'3\' AND items.sale_id=s.sale_id AND items.bn=p.bn) ) AS sales JOIN (`sdb_ome_branch_product` AS bp,`sdb_ome_branch` AS b) ON(bp.branch_id=b.branch_id AND b.attr=\'true\' AND sales.product_id=bp.product_id AND sales.branch_id=b.branch_id) ',$from_time,$to_time);
        $data = $db->select($sql);
        
        if ($data){
            foreach ($data as $value){
                $branch_id = $value['branch_id'];
                $product_id = $value['product_id'];
                $sql = sprintf('SELECT count(*) AS c FROM `sdb_omeanalysts_sale_products` WHERE `product_id`=\'%s\' AND `sales_time`=\'%s\' AND `branch_id`=\'%s\' ',$product_id,$sales_time,$branch_id);
                $tmp = $db->count($sql);
                if (!$tmp){
                    $sdf = array(
                        'product_id' => $product_id,
                        'branch_id' => $branch_id,
                        'sales_time' => $value['sale_time'],
                        'sales_nums' => $value['nums'],
                        'sales_price' => $value['price'],
                    );
                    $sale_productsModel->insert($sdf);
                }
            }
            $data = NULL;
        }
    }

}