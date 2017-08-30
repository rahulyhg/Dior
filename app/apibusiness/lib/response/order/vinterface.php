<?php
/**
* 版本接口
*
* @category apibusiness
* @package apibusiness/response/order/
* @author chenping<chenping@shopex.cn>
* @version $Id: vinterface.php 2013-3-12 17:23Z
*/
interface apibusiness_response_order_vinterface
{

    public function analysis(&$ordersdf);

    public function status_update();

    public function pay_status_update();

    public function ship_status_update();

    public function custom_mark_add();

    public function custom_mark_update();

    public function memo_add();

    public function memo_update();

    public function payment_update();

    public function setPlatform($platform);
}