<?php
/**
* 产品销售排行榜
* @copyright shpoex.cn 2011.11.18
*/
class omeanalysts_crontab_script_productSaleRank{

    /**
    * 按天统计商品类目销售
    * @param 定时执行
    * @return 
    */
    public function statistics(){

        $time = time();
        $last_time = app::get('omeanalysts')->getConf('last_time.productSaleRank');#上次脚本执行时间
        $db = kernel::database();
        $shopModel = &app::get('ome')->model('shop');
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

        $shop_list = $shopModel->getList('shop_id,name');#店铺列表
        for($year=$from_years;$year<=$to_years;$year++){
            for($month=$from_months;$month<=$to_months;$month++){
                if ( $year.$month < date('Ym') ){
                    $to_days = date('t', strtotime($year.'-'.$month));
                }else{
                    $to_days = date("j",time()-24*60*60);
                }
                for($day=$from_days;$day<=$to_days;$day++){
                    $dates = $year.'-'.$month.'-'.$day;
                    if ($shop_list){
                        foreach ( $shop_list as $shop ){
                            $this->_doStatistics($shop['shop_id'],$dates);
                        }
                    }
                }
                $from_days = 1;
            }
        }
        app::get('omeanalysts')->setConf('last_time.productSaleRank', $time);
    }

    /**
    * 统计数据
    * @param $shop_id 店铺ID
    * @param $dates 日期:年-月-日,如2011-11-16
    * @return 
    */
    private function _doStatistics($shop_id,$dates){
        if ( empty($dates) || empty($shop_id) ) return false;

        $db = kernel::database();
        $from_time = strtotime($dates.' 00:00:00');
        $to_time = strtotime($dates.' 23:59:59');
        $productSaleModel = &app::get('omeanalysts')->model('products_sale');

        $sql = sprintf(' SELECT c.bn,sum(c.nums) AS nums,sum(c.nums*c.price) AS amount 
                        FROM (
                        SELECT distinct(item_id),items.bn,items.nums,items.price 
                        FROM `sdb_ome_sales_items` AS items JOIN (`sdb_ome_sales` AS sales,`sdb_ome_iostock` AS io) 
                        ON (items.sale_id=sales.sale_id AND sales.shop_id=\'%s\' AND sales.iostock_bn=io.iostock_bn AND io.type_id=\'3\') 
                        WHERE sales.sale_time>=\'%s\' AND sales.sale_time<=\'%s\'
                        ) AS c GROUP BY c.bn ORDER BY nums desc,amount desc ',$shop_id,$from_time,$to_time);
        $data = $db->select($sql);

        if ($data){
            $sales_time = strtotime($dates);
            foreach ($data as $value){
                $bn = $value['bn'];
                $nums = $value['nums'];
                $sql = sprintf(' SELECT count(*) as c FROM `sdb_omeanalysts_products_sale` WHERE `bn`=\'%s\' AND `sales_time`=\'%s\' AND `shop_id`=\'%s\' ',$bn,$sales_time,$shop_id);
                $tmp = $db->count($sql);
                if (!$tmp){
                    $sql = sprintf('SELECT g.brand_id,p.spec_info,g.name FROM `sdb_ome_products` AS p,`sdb_ome_goods` AS g WHERE p.bn=\'%s\' AND p.goods_id=g.goods_id',$bn);
                    $pg_detail = $db->selectrow($sql);
                    $spec_info = $pg_detail['spec_info'] ? '('.$pg_detail['spec_info'].')' : '';
                    $sdf = array(
                        'sales_time' => $sales_time,
                        'shop_id' => $shop_id,
                        'bn' => $bn,
                        'name' => $pg_detail['name'].$spec_info,
                        'sales_num' => $nums,
                        'sales_amount' => $value['amount'],
                        'brand_id' => $pg_detail['brand_id'],
                    );
                    $productSaleModel->insert($sdf);
                }
            }
            $data = NULL;
        }
    }

}