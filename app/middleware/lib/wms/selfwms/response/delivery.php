<?php
/**
* 发货单
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_response_delivery extends middleware_wms_selfwms_response_common{

    /**
    * 发货状态回传
    * @access public
    * @param Array $params 发货单数据
    * @return Array 标准输出格式
    */
    public function result(&$params){
        
        return $this->response('delivery_result',$params);
    }

}