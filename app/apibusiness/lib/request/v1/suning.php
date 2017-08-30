<?php
/**
* suning(苏宁平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: suning.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_suning extends apibusiness_request_partyabstract
{
    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
        $orderItems = $orderItemModel->getList('shop_goods_id,bn',array('order_id'=>$delivery['order']['order_id'],'item_type' => 'product','delete' => 'false'));

        $order_items = array();
        foreach ($orderItems as $v) {
            if ($v['shop_goods_id']) {
                $order_items[] = $v['shop_goods_id'];
            }
        }

        $orderObjModel = app::get(self::_APP_NAME)->model('order_objects');
        $orderObjs = $orderObjModel->getList('shop_goods_id,obj_id',array('order_id'=>$delivery['order']['order_id'],'obj_type' => 'pkg'));
        foreach ($orderObjs as $v){
            $ocount = $orderItemModel->count(array('order_id'=>$delivery['order']['order_id'],'obj_id'=>$v['obj_id'],'delete' => 'false'));

            if ($v['shop_goods_id'] && $ocount>0) {
                $order_items[] = $v['shop_goods_id'];
            }
        }
        
        $order_items = array_unique($order_items);

        $param = array(
            'tid'               => $delivery['order']['order_bn'],
            'company_code'      => $delivery['dly_corp']['type'],
            'logistics_company' => $delivery['logi_name'] ? $delivery['logi_name'] : '',
            'logistics_no'      => $delivery['logi_no'] ? $delivery['logi_no'] : '',
            'item_list'         => json_encode($order_items),
        );

        return $param;
    }
}