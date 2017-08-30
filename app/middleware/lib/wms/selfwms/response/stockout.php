<?php
/**
* 出库单
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_response_stockout extends middleware_wms_selfwms_response_common{

    /**
    * 出库状态回传
    * @access public
    * @param Array $params 出库单数据
    * @return Array 标准输出格式
    */
    public function result(&$params){
        
        return $this->response('stockout_result',$params);
    }

}