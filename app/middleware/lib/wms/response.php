<?php
/**
* WMS接收接口调用
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_response extends middleware_wms_responseInterface{

    public function __construct($wms_id=''){
        //$this->_router = kernel::single('middleware_wms_router');
        $this->_router = new middleware_wms_router();
        $this->_router->_adapter = $this->_router->getAdapter('response',$wms_id);
        $this->_router->_wms_id = $wms_id;
    }
    
    /**
    * 入库单结果回传
    */
    public function stockin_result($params=array()){
        $params['wms_id'] = $this->_router->_wms_id;
        return $this->_router->response(__FUNCTION__,$params);
    }
    
    /**
    * 出库单结果回传
    */
    public function stockout_result($params=array()){
        $params['wms_id'] = $this->_router->_wms_id;
        return $this->_router->response(__FUNCTION__,$params);
    }
    
    /**
    * 转储单结果回传
    */
    public function stockdump_result($params=array()){
        $params['wms_id'] = $this->_router->_wms_id;
        return $this->_router->response(__FUNCTION__,$params);
    }
    
    /**
    * 发货单结果回传
    */
    public function delivery_result($params=array()){
        $params['wms_id'] = $this->_router->_wms_id;
        #转换物流公司编号
        if(isset($params['logi_id']) && $params['logi_id']){
            $params['logi_id'] = $this->_router->_abstract->getlogiCode($this->_router->_wms_id,$params['logi_id']);
        }

        return $this->_router->response(__FUNCTION__,$params);
    }
    
    /**
    * 退货单结果回传
    */
    public function reship_result($params=array()){
        $params['wms_id'] = $this->_router->_wms_id;
        return $this->_router->response(__FUNCTION__,$params);
    }
    
    /**
    * 库存对账结果回传
    */
    public function stock_result($params=array()){
        $params['wms_id'] = $this->_router->_wms_id;
        return $this->_router->response(__FUNCTION__,$params);
    }
    
    /**
    * 盘点结果回传
    */
    public function inventory_result($params=array()){
        $params['wms_id'] = $this->_router->_wms_id;
        return $this->_router->response(__FUNCTION__,$params);
    }

}
