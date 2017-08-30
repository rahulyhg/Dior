<?php
/**
 * 电子面单接口
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
interface logisticsmanager_waybill_interface
{
    /**
     * 获取渠道电子面单
     *
     * @return void
     * @author 
     **/
    public function request_waybill();

    /**
     * 回传电子面单
     *
     * @return void
     * @author 
     **/
    public function delivery($delivery_id);

}