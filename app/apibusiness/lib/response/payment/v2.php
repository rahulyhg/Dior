<?php
/**
* 支付单 版本二
*
* @category apibusiness
* @package apibusiness/response/payment
* @author chenping<chenping@shopex.cn>
* @version $Id: v2.php 2013-3-12 17:23Z
*/
class apibusiness_response_payment_v2 extends apibusiness_response_payment_abstract
{

    public function add()
    {
        parent::add();
        $this->_apiLog['info'][] = '版本二不做支付单添加处理';
    }

    public function status_update()
    {
        parent::status_update();
        $this->_apiLog['info'][] = '版本二不做支付单状态处理'; 
    }
}