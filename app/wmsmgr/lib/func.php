<?php
/**
* 公共函数库
* 
* @copyright shopex.cn 2013.4.10
* @author dongqiujin<123517746@qq.com>
*/
class wmsmgr_func{

    /**
    * 根据wmsmgr_id获取适配器
    *
    * @access public
    * @param String $channel_id 渠道ID
    * @return Array 适配器
    */
    public function getAdapterByChannelId($channel_id=''){
        return kernel::single('channel_func')->getAdapterByChannelId($channel_id);
    }

    /**
    * 获取wms适配器列表
    * @access public
    * @return Array 适配器
    */
    public function getWmsAdapterList(){
        return middleware_adapter::getWmsList();
    }

    /**
    * 存储渠道与适配器的关系
    * @access public
    * @return bool
    */
    public function saveChannelAdapter($channel_id,$adapter=''){
        return kernel::single('channel_func')->saveChannelAdapter($channel_id,$adapter);
    }

    /**
    * 根据wms_id、系统物流公司编号获取wms物流公司编号
    * @access public
    * @return string
    */
    public function getWmslogiCode($channel_id,$sys_express_corp_bn=''){
        $express_relation_mdl = app::get('wmsmgr')->model('express_relation');
        $data = $express_relation_mdl->getlist('*',array('wms_id'=>$channel_id,'sys_express_bn'=>$sys_express_corp_bn));
        return isset($data[0]['wms_express_bn']) ? $data[0]['wms_express_bn'] : '';
    }

    /**
    * 根据wms_id、wms物流公司编号获取系统物流公司编号
    * @access public
    * @return string
    */
    public function getlogiCode($channel_id,$wms_express_corp_bn=''){
        $express_relation_mdl = app::get('wmsmgr')->model('express_relation');
        $data = $express_relation_mdl->getlist('*',array('wms_id'=>$channel_id,'wms_express_bn'=>$wms_express_corp_bn));
        return isset($data[0]['sys_express_bn']) ? $data[0]['sys_express_bn'] : '';
    }

    /**
    * 根据wms_id、系统店铺编号获取wms售达方编号
    * @access public
    * @return string
    */
    public function getWmsShopCode($wms_id,$shop_bn=''){
        $shop_config = app::get('finance')->getConf('shop_config_'.$wms_id);
        return $shop_config[$shop_bn];
    }

}