<?php
/**
* WMS发起接口调用
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_request extends middleware_wms_requestInterface{

    public function __construct($wms_id=''){
        //$this->_router = kernel::single('middleware_wms_router');
        $this->_router = new middleware_wms_router();
        $this->_router->_adapter = $this->_router->getAdapter('request',$wms_id);
        $this->_router->_wms_id = $wms_id;
    }
 
    /**
    * 设置用户异步回调
    */
    public function setUserCallback($callback_class,$callback_method,$callback_params=null){
        $this->_router->callback_class = $callback_class;
        $this->_router->callback_method = $callback_method;
        $this->_router->callback_params = $callback_params;
        return $this;
    }
    
    /**
    * 入库单创建
    */
    public function stockin_create(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 入库单取消
    */
    public function stockin_cancel(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    
    /**
     * 入库单查询
     * @param  
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function stockin_search(&$sdf,$sync=false)
    {
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }
    /**
    * 出库单创建
    */
    public function stockout_create(&$sdf,$sync=false){
        if ($sdf['logi_code']) {
            #wms物流公司转换
            $sdf['wms_logi_code'] = $this->_router->_abstract->getWmslogiCode($this->_router->_wms_id,$sdf['logi_code']);

        }
        $sdf['wms_id'] = $this->_router->_wms_id;
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 出库单取消
    */
    public function stockout_cancel(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    
    /**
     * 出库单查询
     * @param 
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function stockout_search(&$sdf,$sync = false)
    {
         return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }
    /**
    * 转储单创建
    */
    public function stockdump_create(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 转储单取消
    */
    public function stockdump_cancel(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单创建
    */
    public function delivery_create(&$sdf,$sync=false){
        $sdf['wms_id'] = $this->_router->_wms_id;
        #wms物流公司转换
        $sdf['wms_logi_code'] = $this->_router->_abstract->getWmslogiCode($this->_router->_wms_id,$sdf['logi_code']);
        #wms售达方编号
        $sdf['shop_code'] = $this->_router->_abstract->getWmsShopCode($this->_router->_wms_id,$sdf['shop_code']);
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单暂停
    */
    public function delivery_pause(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单恢复
    */
    public function delivery_renew(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 发货单取消
    */
    public function delivery_cancel(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

 
    /**
     * 发货单查询
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function delivery_search(&$sdf,$sync = false)
    {
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }
    /**
    * 商品添加
    */
    public function goods_add(&$sdf,$sync=false){
            $rs = $this->_router->page_request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 商品编辑
    */
    public function goods_update(&$sdf,$sync=false){
            $rs = $this->_router->page_request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 退货单创建
    */
    public function reship_create(&$sdf,$sync=false){
        $sdf['wms_id'] = $this->_router->_wms_id;
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 退货单取消
    */
    public function reship_cancel(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }
     
    /**
     * 退货查询
     * @param 
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function reship_search(&$sdf,$sync = false)
    {
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 是否允许暂停订单
    */
    public function isAllowPauseOrder(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
    * 暂停订单
    */
    public function pauseOrder(&$sdf,$sync=false){
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
     * 获取仓库
     * @param  
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_warehouse_list( &$sdf,$sync)
    {
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }
    
    /**
     * 获取物流公司
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_logistics_list( &$sdf,$sync)
    {
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }

    /**
     * 供应商创建通知
     * @param  array
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function supplier_create(&$sdf,$sync)
    {
        
        return $this->_router->request(__FUNCTION__,$sdf,$sync);
    }
}