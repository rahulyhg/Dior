<?php
/**
* 商品类目销售统计
* @copyright shpoex.cn 2011.11.18
*/
class omeanalysts_crontab_script_catSaleStatis{

    /**
    * 按天统计商品类目销售
    * @param crontab每分钟触发
    * @return 
    */
    public function statistics(){

        $time = time();
        $last_time = app::get('omeanalysts')->getConf('last_time.catSaleStatis');#上次脚本执行时间
        $db = kernel::database();
        if (!$last_time){
            // 取出最早的销售时间
            //$sql = 'SELECT `sale_time` FROM `sdb_ome_sales` WHERE `sale_time` IS NOT NULL ORDER BY `sale_time` ASC';
            //$last_sales = $db->selectrow($sql);
            //$last_time = $last_sales['sale_time'];
            $last_time = $time - 86400;
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
        app::get('omeanalysts')->setConf('last_time.catSaleStatis', $time);
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
        $catSaleStatisModel = &app::get('omeanalysts')->model('cat_sale_statis');

        $sql = sprintf(' SELECT c.shop_id,g.type_id,g.brand_id,sum(c.nums) AS nums,sum(c.nums*c.price) AS amount 
                        FROM (
                        SELECT distinct(item_id),sales.shop_id,items.bn,items.nums,items.price 
                        FROM `sdb_ome_sales_items` AS items JOIN (`sdb_ome_sales` AS sales,`sdb_ome_iostock` AS io) 
                        ON (items.sale_id=sales.sale_id AND sales.iostock_bn=io.iostock_bn AND io.type_id=\'3\') 
                        WHERE sales.sale_time>=\'%s\' AND sales.sale_time<=\'%s\'
                        ) AS c
                        JOIN (`sdb_ome_products` AS p,`sdb_ome_goods` AS g) ON (c.bn=p.bn AND p.goods_id=g.goods_id)  GROUP BY c.shop_id,g.type_id,g.brand_id',$from_time,$to_time);
        $data = $db->select($sql);

        if ($data){
            $sales_time = strtotime($dates);
            foreach ($data as $value){
                $shop_id = $value['shop_id'];
                $type_id = $value['type_id'];
                $brand_id = $value['brand_id'];
                $sql = sprintf('SELECT count(*) as c FROM `sdb_omeanalysts_cat_sale_statis` WHERE `sales_time`=\'%s\' AND `shop_id`=\'%s\' AND `type_id`=\'%s\' AND `brand_id`=\'%s\'',$sales_time,$shop_id,$type_id,$brand_id);
                $tmp = $db->count($sql);
                if (!$tmp){
                    $sdf = array(
                        'sales_time' => $sales_time,
                        'shop_id' => $shop_id,
                        'type_id' => $type_id,
                        'brand_id' => $brand_id,
                        'sales_num' => $value['nums'],
                        'sales_amount' => $value['amount'],
                    );
                    $catSaleStatisModel->insert($sdf);
                }
            }
            $data = NULL;
        }  
    }

}