<?php
/**
 * 库存service
 * 有关库存方面的扩展功能都可以使用此服务
 * @author dongqiujing
 * @package ome_service_stock
 * @copyright www.shopex.cn 2010.10.14
 *
 */
class ome_service_stock{

    public function __construct(&$app)
    {
        $this->app = $app;

        $this->router = kernel::single('apibusiness_router_request');
    }

    /**
     * 清除预占库存
     * @access public
     * @param int $order_id 订单ID
     */
    public function clean_freeze($order_id){
        $orderModel = $this->app->model('orders');
        $order = $orderModel->dump($order_id);

        $this->router->setShopId($order['shop_id'])->clean_stock_freeze($order);
        //kernel::single("ome_rpc_request_stock")->clean_freeze($order_id);
    }

    /**
     * 更新库存
     * @access public
     * @param array $stocks 需更新的货号数量多维数组 array('bn'=>'22')
     * @param int $shop_id 店铺ID
     * @param string $shop_type 店铺类型
     */
    public function update_stock($stocks,$shop_id,$shop_type=''){
        $this->router->setShopId($shop_id)->update_stock($stocks);
        //kernel::single("ome_rpc_request_stock")->stock_update($stocks,$shop_id,$shop_type);
    }

	/**
	 * 计算回写的最大库存值
	 * @access public
	 * @param string $shop_id 店铺ID
	 * @param int $store_sync_from 回写开始时间
	 * @param int $store_sync_end 回写结束时间
	 * @param int $limit 一次性回写的库存个数
	 * @param int $offset 当前回写的库存位置
	 * @return array
	 */
	public function calculate_stock($shop_id,$store_sync_from='',$store_sync_end='',$offset='0',$limit='100'){
        $shopObj = &app::get('ome')->model("shop");
        $shop = $shopObj->dump($shop_id);

        //TODO：可在店铺上进行设置是否回写库存
        //$shop_stock_config = &app::get('ome')->getConf('shop_stock_config_'.$shop_id);
        $shop_stock_config = 'true';//TODO:默认为true

        if ($shop_stock_config == 'true'){
            $db = kernel::database();
            $where = " `max_store_lastmodify`>'".$store_sync_from."' AND `max_store_lastmodify` IS NOT NULL ";
            $sql = "SELECT `product_id`,`bn`,`store_freeze`
                    FROM `sdb_ome_products`
                    WHERE $where LIMIT ".$offset.",".$limit;
            $bn_list = $db->select($sql);

            $stocks = array();
            if($bn_list){
                foreach($bn_list as $k=>$v){
                    //获取bn线上仓库库存总和
                    $sql = " SELECT sum(bp.store) as store FROM `sdb_ome_branch_product` bp,`sdb_ome_branch` b
                             WHERE bp.`product_id`='".$v['product_id']."' and bp.`branch_id`=b.`branch_id` AND b.`attr`='true' ";
                    $total_store = $db->selectrow($sql);
                    if (IS_NULL($total_store['store']) || $total_store['store'] === '') continue;

                    //回写库存值 = 线上仓库库存总和 - 预占库存
                    $quantity = $total_store['store'] - $v['store_freeze'];

                    $storeConfig = ome_shop_type::get_store_config();
                    if($storeConfig[$shop['shop_type']] && $storeConfig[$shop['shop_type']] == 'on'){
                        //获取店铺冻结库存
                        $sql = "SELECT sum(oi.nums) as number,sum(oi.sendnum) as sendnum FROM sdb_ome_order_items as oi,sdb_ome_orders o
                            where o.order_id = oi.order_id
                            and oi.product_id = '".$v['product_id']."'
                            and oi.`delete` = 'false'
                            and oi.sendnum < oi.nums
                            and o.ship_status in ('0','2')
                            and o.status = 'active'
                            and o.shop_id = '".$shop_id."'
                            group by oi.product_id
                            ";
                        $shop_store = $db->selectrow($sql);
                        $shop_store_freeze = 0;
                        $shop_store_freeze = $shop_store['number']-$shop_store['sendnum'];
                        $shop_store_freeze = ($shop_store_freeze>0)? $shop_store_freeze : 0;
                        $quantity = $quantity+$shop_store_freeze;
                    }

                    if ($quantity < 0) $quantity = 0;
                        /**
                         * memo 存放额外信息(根据前端需要)
                         * @author yangminsheng
                         **/
                    $memo = array(
                        'store_freeze' => $shop_store_freeze,
                        'last_modified' => $v['last_modified']?$v['last_modified']:time(),
                    );
                    $stocks[] = array(
                        'bn' => trim($v['bn']),
                        'quantity' => $quantity,
                        'memo' => json_encode($memo),
                    );
                }
                if(is_array($stocks) && count($stocks)>0){
                    return $stocks;
                }
                return true;
            }
        }else{
            return null;
        }
	}


	/**
	 * 计算回写的最大库存值
	 * @access public
	 * @param string $shop_id 店铺ID
	 * @param int $store_sync_from 回写开始时间
	 * @param int $store_sync_end 回写结束时间
	 * @param int $limit 一次性回写的库存个数
	 * @param int $offset 当前回写的库存位置
	 * @return array
	 */
	public function shop_calculate_stock($shop_id,$store_sync_from='',$store_sync_end='',$offset='0',$limit='100'){
        $shopObj = &app::get('ome')->model("shop");
        $shop = $shopObj->dump($shop_id);

        //TODO：可在店铺上进行设置是否回写库存
        //$shop_stock_config = &app::get('ome')->getConf('shop_stock_config_'.$shop_id);
        $shop_stock_config = 'true';//TODO:默认为true

        if ($shop_stock_config == 'true' && !empty($shop) && is_array($shop)){
            $db = kernel::database();
            $sql = "SELECT `product_id`,`bn`,`store_freeze`
                    FROM `sdb_ome_products`
                    WHERE 1 LIMIT ".$offset.",".$limit;
            $bn_list = $db->select($sql);

            $stocks = array();
            if($bn_list){
                foreach($bn_list as $k=>$v){
                    //获取bn线上仓库库存总和
                    $sql = " SELECT sum(bp.store) as store FROM `sdb_ome_branch_product` bp,`sdb_ome_branch` b
                             WHERE bp.`product_id`='".$v['product_id']."' and bp.`branch_id`=b.`branch_id` AND b.`attr`='true' ";
                    $total_store = $db->selectrow($sql);
                    if (IS_NULL($total_store['store']) || $total_store['store'] === '') continue;

                    //回写库存值 = 线上仓库库存总和 - 预占库存
                    $quantity = $total_store['store'] - $v['store_freeze'];

                    $storeConfig = ome_shop_type::get_store_config();
                    if($storeConfig[$shop['shop_type']] && $storeConfig[$shop['shop_type']] == 'on'){
                        //获取店铺冻结库存
                        $sql = "SELECT sum(oi.nums) as number,sum(oi.sendnum) as sendnum FROM sdb_ome_order_items as oi,sdb_ome_orders o
                            where o.order_id = oi.order_id
                            and oi.product_id = '".$v['product_id']."'
                            and oi.`delete` = 'false'
                            and oi.sendnum < oi.nums
                            and o.ship_status in ('0','2')
                            and o.status = 'active'
                            and o.shop_id = '".$shop_id."'
                            group by oi.product_id
                            ";
                        $shop_store = $db->selectrow($sql);
                        $shop_store_freeze = 0;
                        $shop_store_freeze = $shop_store['number']-$shop_store['sendnum'];
                        $shop_store_freeze = ($shop_store_freeze>0)? $shop_store_freeze : 0;
                        $quantity = $quantity+$shop_store_freeze;
                    }

                    if ($quantity < 0) $quantity = 0;
                        /**
                         * memo 存放额外信息(根据前端需要)
                         * @author yangminsheng
                         **/
                    $memo = array(
                        'store_freeze' => $v['store_freeze'],
                        'last_modified' => $v['last_modified']?$v['last_modified']:time(),
                    );
                    $stocks[] = array(
                        'bn' => trim($v['bn']),
                        'quantity' => $quantity,
                        'memo' => json_encode($memo),
                    );
                }
                if(is_array($stocks) && count($stocks)>0){
                    return $stocks;
                }
                return true;
            }
        }else{
            return null;
        }
	}

}