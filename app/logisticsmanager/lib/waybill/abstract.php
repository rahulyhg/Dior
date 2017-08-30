<?php
/**
 * 电子面单抽象类
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: 2014-01-28 11:09Z
 */
abstract class logisticsmanager_waybill_abstract 
{
    protected $_channel = array();

    protected $_shop = array();

    const _APP_NAME = 'logisticsmanager';

    public function setChannel($channel)
    {
        $this->_channel = $channel;

        return $this;
    }

    public function setShop($shop)
    {
        $this->_shop = $shop;
        
        return $this;
    }

    /**
     * 获取缓存中的运单号前动作
     *
     * @return void
     * @author 
     **/
    public function pre_get_waybill()
    {
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');

        return $rs;
    }

    /**
     * 回传电子面单
     *
     * @return void
     * @author 
     **/
    public function delivery($delivery_id)
    {
        $rs = array('rsp'=>'succ','msg'=>'','data'=>'');

        return $rs;
    }
}