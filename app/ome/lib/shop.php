<?php
/**
* 店铺接口类
* @author dqiujing@gmail.com
* @copyright shopex.cn
* @date 2012.12.7
*/
class ome_shop{

    private static $_shopid_instance = array();
    private static $_nodeid_instance = array();

    /**
    * 通过shop_id获取店铺信息
    * 从数据表获取
    * @access public
    * @param String $shop_id 店铺ID
    * @return Array 店铺信息
    */
    function getRowByShopId($shop_id=''){
        if (empty($shop_id)) return NULL;

        if ( empty(self::$_shopid_instance[$shop_id]) ) {
            $this->getRow(array('shop_id' => $shop_id));
        }

        return self::$_shopid_instance[$shop_id];
    }

    /**
    * 通过node_id获取店铺信息
    * 先从KV获取，为空则从数据表获取
    * @access public
    * @param String $node_id 节点ID
    * @return Array 店铺信息
    */
    function getRowByNodeId($node_id=''){
        if (empty($node_id)) return NULL;

        if ( empty(self::$_nodeid_instance[$node_id]) ) {
            $this->getRow(array('node_id' => $node_id));
        }

        return self::$_nodeid_instance[$node_id];
    }

    /**
    * 根据条件获取店铺信息
    * @access public
    * @param Array $filter 查询条件
    * @param String $col 查询字段
    * @return Array 店铺信息
    */
    function getRow($filter='',$col='*'){
        $shopObj = &app::get('ome')->model('shop');
        $shop = $shopObj->getRow($filter,$col);

        if ($shop) {
            self::$_shopid_instance[$shop['shop_id']] = $shop;
            self::$_nodeid_instance[$shop['node_id']] = $shop;
        }
        
        return $shop;
    }

    /**
    * 根据已绑定的店铺
    * @access public
    * @param Array $filter 查询条件
    * @param String $col 查询字段
    * @return Array 店铺列表
    */
    function shop_list($filter='',$col='*'){
        if (!is_array($filter)){
            $filter = array('shop_id'=>$filter);
        }
        $shopObj = &app::get('ome')->model('shop');
        $shop_list = $shopObj->getList($col,$filter,0,-1);
        if ($shop_list){
            foreach ($shop_list as $key=>$val){
                if (empty($val['node_id'])){
                    unset($shop_list[$key]);
                }
            }
        }
        return $shop_list;
    }


}