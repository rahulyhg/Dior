<?php
/**
* 淘宝超卖插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: tboversold.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_tboversold extends apibusiness_response_order_plugin_abstract
{
    /**
     * 更新前处理
     *
     * @return void
     * @author 
     **/
    public function preUpdate()
    {
        $order_id = $this->_platform->_tgOrder['order_id'];
        $tgObject = array();
        foreach ($this->_platform->_tgOrder['order_objects'] as $object) {
            $key = sprintf('%u',crc32($order_id . '-' . $object['shop_goods_id']));
            $tgObject[$key] = $object;
        }

        $is_oversold = false;
        foreach ($this->_platform->_ordersdf['order_objects'] as $object) {
            if ($object['is_oversold'] == true) {
                $key = sprintf('%u',crc32($order_id . '-' . $object['shop_goods_id']));
                if ($tgObject[$key]) {
                    $obj_id = $tgObject[$key]['obj_id'];

                    $objkey = sprintf('%u',crc32(trim($object['bn']) . '-' . trim($object['obj_type'])));

                    $this->_platform->_newOrder['order_objects'][$objkey]['obj_id'] = $obj_id;
                    $this->_platform->_newOrder['order_objects'][$objkey]['is_oversold'] = '1';
                    
                    $is_oversold = true;

                    $this->_platform->_apiLog['info'][] = '淘宝超卖更新，$sdf：'.var_export($this->_platform->_newOrder['order_objects'][$objkey],true);
                }
            }
        }

        if ($is_oversold) {
            $this->_platform->_newOrder['auto_status'] = $this->_platform->_tgOrder['auto_status'] | omeauto_auto_const::__OVERSOLD_CODE;
        }
    }
}