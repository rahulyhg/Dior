<?php
/**
* alibaba(阿里巴巴平台)接口请求实现
*
* @category apibusiness
* @package apibusiness/lib/request/v1
* @author chenping<chenping@shopex.cn>
* @version $Id: alibaba.php 2013-10-11 10:44Z
*/
class apibusiness_request_v1_alibaba extends apibusiness_request_partyabstract
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
        $orderEntryIds = array();
        foreach ((array) $delivery['order']['order_objects'] as $object) {
            $orderEntryIds[] = $object['oid'];
        }

        $params = array(
            'company_code'    => trim($delivery['dly_corp']['type']),
            'memberId'        => $delivery['order']['sellermemberid'],
            'tid'             => $delivery['order']['order_bn'],
            'orderEntryIds'   => implode(',', $orderEntryIds),
            'tradeSourceType' => 'cbu-trade',
            'remarks'         => '',
            'company_name'    => $delivery['dly_corp']['name'],
            'logistics_no'    => $delivery['logi_no'],
            'ship_date'       => date('Y-m-d H:i:s',$delivery['delivery_time']),
        );

        return $params;
    }

    protected function format_delivery($delivery)
    {
        $delivery = parent::format_delivery($delivery);
        if ($delivery) {
            // 读取扩展表sellermemberid
            $orderExtendModel = app::get(self::_APP_NAME)->model('order_extend');
            $extend = $orderExtendModel->dump(array('order_id' => $delivery['order']['order_id']),'sellermemberid');
            $delivery['order']['sellermemberid'] = $extend['sellermemberid'];

            $orderObjModel = app::get(self::_APP_NAME)->model('order_objects');
            $objects = $orderObjModel->getList('oid,order_id,obj_id',array('order_id' => $delivery['order']['order_id']));

            /*
            $orderItemModel = app::get(self::_APP_NAME)->model('order_items');
            $items = $orderItemModel->getList('order_id,obj_id',array('delete'=>'true'));
            if ($items) {
                $delObj = array();
                foreach ($items as $key => $value) {
                    $delObj[] = $value['obj_id'];
                }

                foreach ($objects as $key => $object) {
                    if (!$object['oid'] || in_array($object['obj_id'],$delObj)) {
                        unset($objects[$key]);
                    }
                }
            }
            */

            /*xiayuanjun 调整如果阿里巴巴订单修改过，还是取原来的oid作为参数回写*/
            foreach ($objects as $key => $object) {
                if (!$object['oid']) {
                    unset($objects[$key]);
                }
            }

            $delivery['order']['order_objects'] = $objects;
        }

        return $delivery;
    }

    public function update_order_shippinginfo($order)
    {
        
    }
}