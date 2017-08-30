<?php
/**
* 淘宝赠品插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
* @author chenping<chenping@shopex.cn>
* @version $Id: tbgift.php 2013-3-12 17:23Z
*/
class apibusiness_response_order_plugin_tbgift extends apibusiness_response_order_plugin_abstract
{
    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        if ('true' == app::get('ome')->getConf('ome.preprocess.tbgift')) {
            $tbgift = json_decode($this->_platform->_ordersdf['other_list'],true);
            kernel::single('ome_preprocess_tbgift')->save($this->_platform->_newOrder['order_id'],$tbgift);

            $this->_platform->_apiLog['info'][] = '淘宝赠品标准$sdf结构：'.var_export($tbgift,true);
        }
    }
}