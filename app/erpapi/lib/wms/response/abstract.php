<?php
/**
 * WMS 发货单
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
abstract class erpapi_wms_response_abstract
{
    protected $__channelObj;

    public $__apilog;

    public function init(erpapi_channel_abstract $channel)
    {
        $this->__channelObj = $channel;

        return $this;
    }
}
