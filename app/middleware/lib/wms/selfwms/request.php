<?php
/**
* 自有仓储发起调用类
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_selfwms_request extends middleware_wms_requestInterface{

    public function __construct(){
        $this->_router = kernel::single('middleware_wms_selfwms_router');
    }

    /**
    * 设置用户异步回调
    */
    public function setUserCallback($callback_class,$callback_method,$callback_params=null){
        $this->_router->callback_class = $callback_class;
        $this->_router->callback_method = $callback_method;
        $this->_router->callback_params = $callback_params;
    }

    /**
    * 入库单创建
    */
    public function stockin_create($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_stockin';
        return $this->_router->request('入库单创建',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 入库单取消
    */
    public function stockin_cancel($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_stockin';
        return $this->_router->request('入库单取消',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 出库单创建
    */
    public function stockout_create($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_stockout';
        return $this->_router->request('出库单创建',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 出库单取消
    */
    public function stockout_cancel($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_stockout';
        return $this->_router->request('出库单取消',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 转储单创建
    */
    public function stockdump_create($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_stockdump';
        return $this->_router->request('转储单创建',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 转储单取消
    */
    public function stockdump_cancel($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_stockdump';
        return $this->_router->request('转储单取消',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单创建
    */
    public function delivery_create($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_delivery';
        return $this->_router->request('发货单创建',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单暂停
    */
    public function delivery_pause($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_delivery';
        return $this->_router->request('发货单暂停',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单恢复
    */
    public function delivery_renew($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_delivery';
        return $this->_router->request('发货单恢复',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单取消
    */
    public function delivery_cancel($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_delivery';
        return $this->_router->request('发货单取消',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 退货单创建
    */
    public function reship_create($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_reship';
        return $this->_router->request('退货单创建',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 退货单取消
    */
    public function reship_cancel($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_reship';
        return $this->_router->request('退货单取消',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 商品添加
    */
    public function goods_add($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_goods';
        return $this->_router->request('商品添加',$class,__FUNCTION__,$sdf,$sync);
    }

    /**
    * 商品编辑
    */
    public function goods_update($sdf=array(),$sync=false){
        $class = 'middleware_wms_selfwms_request_goods';
        return $this->_router->request('商品编辑',$class,__FUNCTION__,$sdf,$sync);
    }

}