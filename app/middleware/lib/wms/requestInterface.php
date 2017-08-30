<?php
/**
* WMS发起接口列表
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_requestInterface {

    public function __construct(){
        $this->_abstract = kernel::single('middleware_wms_abstract');
    }

    /**
    * 入库单创建
    *
    * @access public
    * @param Array $sdf 入库单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function stockin_create($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 入库单取消
    *
    * @access public
    * @param Array $sdf 入库单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function stockin_cancel($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 出库单创建
    *
    * @access public
    * @param Array $sdf 出库单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function stockout_create($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 出库单取消
    *
    * @access public
    * @param Array $sdf 出库单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function stockout_cancel($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 库内转储单创建
    *
    * @access public
    * @param Array $sdf 转储单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function stockdump_create($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 库内转储单取消
    *
    * @access public
    * @param Array $sdf 转储单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function stockdump_cancel($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 发货单创建
    *
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function delivery_create($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 发货单暂停
    *
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function delivery_pause($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }
    
    /**
    * 发货单恢复
    *
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function delivery_renew($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 发货单取消
    *
    * @access public
    * @param Array $sdf 发货单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function delivery_cancel($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 商品添加
    *
    * @access public
    * @param Array $sdf 商品数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function goods_add($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 商品编辑
    *
    * @access public
    * @param Array $sdf 商品数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function goods_update($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 退货单创建
    *
    * @access public
    * @param Array $sdf 退货单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function reship_create($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 退货单取消
    *
    * @access public
    * @param Array $sdf 退货单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function reship_cancel($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 是否允许暂停订单
    *
    * @access public
    * @param Array $sdf 订单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function isAllowPauseOrder($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 暂停订单
    *
    * @access public
    * @param Array $sdf 订单数据
    * @param String $sync 同异步类型：true(同步)、false(异步)，默认false
    * @return Array 标准输出格式
    */
    public function pauseOrder($sdf=array(),$sync=false){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }
 
    
    /**
     * 获取仓库列表
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function get_warehouse_list($sdf = array(),$sync=false)
    {
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

     /**
     * 
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function supplier_create($sdf = array(),$sync=false)
    {
        
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }
}