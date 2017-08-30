<?php
/**
* ecstore(ecstore系统)直销订单处理 版本二
*
* @category apibusiness
* @package apibusiness/response/order/shopex/ecstore
* @author chenping<chenping@shopex.cn>
* @version $Id: b2cv2.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_shopex_ecstore_b2cv2 extends apibusiness_response_order_shopex_ecstore_abstract
{
    protected function accept_dead_order()
    {
       $result = parent::accept_dead_order();
        if ($result === false) {
            if ($this->_ordersdf['status'] == 'dead' ) {
                unset($this->_apiLog['info']['msg']);
                return true;
            }
        }
       return $result; 
    }
}