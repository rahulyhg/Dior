<?php
/**
* 库存对账
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_response_stock extends middleware_wms_selfwms_response_common{

    /**
    * 库存对账入库状态回传
    * @access public
    * @param Array $params 库存对账数据
    * @return Array 标准输出格式
    */
    public function result(&$params){
        
        return $this->response('stock_result',$params);
    }

}