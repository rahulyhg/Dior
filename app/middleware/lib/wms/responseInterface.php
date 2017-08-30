<?php
/**
* WMS接收接口列表
* 
* @copyright shopex.cn 2013.04.08
* @author dongqiujing<123517746@qq.com>
*/
class middleware_wms_responseInterface{

    public function __construct(){
        $this->_abstract = kernel::single('middleware_wms_abstract');
    }

    /**
    * 入库单结果回传
    * @param Array $params 回传参数
    * @return Array 标准输出格式
    */
    public function stockin_result($params=array()){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 出库单结果回传
    * @param Array $params 回传参数
    * @return Array 标准输出格式
    */
    public function stockout_result($params=array()){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 转储单结果回传
    * @param Array $params 回传参数
    * @return Array 标准输出格式
    */
    public function stockdump_result($params=array()){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 发货单结果回传
    * @param Array $params 回传参数
    * @return Array 标准输出格式
    */
    public function delivery_result($params=array()){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 退货单结果回传
    * @param Array $params 回传参数
    * @return Array 标准输出格式
    */
    public function reship_result($params=array()){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 库存对账结果回传
    * @param Array $params 回传参数
    * @return Array 标准输出格式
    */
    public function stock_result($params=array()){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }

    /**
    * 盘点结果回传
    * @param Array $params 回传参数
    * @return Array 标准输出格式
    */
    public function inventory_result($params=array()){
        return $this->_abstract->msgOutput('fail','接口方法不存在','w402');
    }
    
}