<?php
/**
* dangdang(当当平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: dangdang.php 2013-13-12 14:44Z
*/
class apibusiness_request_v1_dangdang extends apibusiness_request_partyabstract
{
    /**
     * 获取必要的发货数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author 
     **/
    protected function format_delivery($delivery)
    {
        $delivery = parent::format_delivery($delivery);

        // 发货单明细
        $deliItemModel = app::get(self::_APP_NAME)->model('delivery_items');
        $develiy_items = $deliItemModel->getList('product_name as name,bn,number',array('delivery_id'=>$delivery['delivery_id']));

        // 过滤发货单明细中的空格
        foreach((array)$develiy_items as $key=>$item){
            $develiy_items[$key] = array_map('trim', $item);
        }

        $delivery['delivery_items'] = $develiy_items;

        // 会员信息
        $memberModel = app::get(self::_APP_NAME)->model('members');
        $delivery['member'] = $memberModel->dump(array('member_id'=>$delivery['member_id']),'uname,name');

        return $delivery;
    }// TODO TEST

    /**
     * 获取发货参数
     *
     * @param Array $delivery 发货单信息
     * @return Array
     * @author 
     **/
    protected function getDeliveryParam($delivery)
    {
        // 判断是否存在捆绑商品
        
        $orderObjectModel = app::get(self::_APP_NAME)->model('order_objects');
        $count = $orderObjectModel->count(array('order_id' => $delivery['order']['order_id'] , 'obj_type' => 'pkg'));
        if($count > 0){
            $orderObj = $orderObjectModel->getList('*',array('order_id'=>$delivery['order']['order_id']));

            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $orderItems = $orderItemModel->getList('*',array('order_id'=>$delivery['order']['order_id'],'delete'=>'false'));
            $order_items = array();
            foreach ($orderItems as $key => $item) {
                $order_items[$item['obj_id']][] = $item;
            }
            unset($orderItems);

            $delivery_items = array();
            foreach ($orderObj as $obj) {
                if ($order_items[$obj['obj_id']]) {
                    if ($obj['obj_type'] == 'pkg') {
                        $delivery_items[] = array(
                            'oid' => $delivery['order']['order_bn'],
                            'itemId' => $obj['shop_goods_id'],
                            'num' => $obj['quantity'],
                        );    
                    } else {
                        foreach ($order_items[$obj['obj_id']] as $item) {
                            $delivery_items[] = array(
                                'oid' => $delivery['order']['order_bn'],
                                'itemId' =>$item['shop_goods_id'],
                                'num' => $item['sendnum'],
                                'promotion_id'=>$item['promotion_id'],
                            );
                        }
                    }
                }
            }
        } else{
            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $orderItems = $orderItemModel->getList('shop_goods_id,bn,promotion_id',array('order_id'=>$delivery['order']['order_id']));

            $order_items = array();
            foreach ($orderItems as $v) {
                $order_items[$v['bn']] = $v;
            }

            $delivery_items = array();
            foreach ($delivery['delivery_items'] as $v) {
                $delivery_items[] = array(
                    'oid'    => $delivery['order']['order_bn'],
                    'itemId' => $order_items[$v['bn']]['shop_goods_id'],
                    'num'    => $v['number'],
                    'promotion_id'=>$order_items[$v['bn']]['promotion_id'],
                );
            }
        }
        $param = array(
            'tid'          => $delivery['order']['order_bn'],
            'logisticstel' => '13612345678',//物流电话 - 当当物流电话固定写死
            'company_name' => $delivery['logi_name'] ? $delivery['logi_name'] : '',//物流公司
            'logistics_no' => $delivery['logi_no'] ? $delivery['logi_no'] : '',//物流单号
            'ship_date'    => date('Y-m-d H:i:s',$delivery['last_modified']),//发货时间
            'item_list'    => json_encode($delivery_items),//发货明细
        );

        return $param;
    }// TODO TEST

   /**
     * 售后请求
     * @param   array    $returninfo    售后信息
     * @return  
     * @access  protected
     * @author 
     */
    protected function update_aftersale_request($returninfo)
    {
    }

    public function update_order_shippinginfo($order)
    {
        
    }
}