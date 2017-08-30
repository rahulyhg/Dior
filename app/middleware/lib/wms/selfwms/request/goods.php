<?php
/**
* 商品
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_request_goods extends middleware_wms_selfwms_request_common{

    /**
    * 商品添加
    * @access public
    * @param Array $sdf 商品数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function goods_add(&$sdf,$sync=false){
        
        $wms_class = 'wms_event_receive_goods';
        $wms_method = 'create';
        return $this->request($wms_class,$wms_method,$sdf);
    }

    /**
    * 商品编辑
    * @access public
    * @param Array $sdf 商品数据
    * @param String $sync 同异步类型：false(同步)、true(异步)，默认true
    * @return Array 标准输出格式
    */
    public function goods_update(&$sdf,$sync=false){
        
        $wms_class = 'wms_event_receive_goods';
        $wms_method = 'updateStatus';
        return $this->request($wms_class,$wms_method,$sdf);
    }

}