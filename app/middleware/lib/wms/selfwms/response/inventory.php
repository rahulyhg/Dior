<?php
/**
* 盘点
*
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_response_inventory extends middleware_wms_selfwms_response_common{

    /**
    * 盘点入库状态回传
    * @access public
    * @param Array $params 盘点数据
    * @return Array 标准输出格式
    */
    public function result(&$params){
        
        return $this->response('inventory_result',$params);
    }

}