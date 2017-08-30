<?php
/**
* yintai(银泰平台)订单处理 抽象类
*
* @category apibusiness
* @package apibusiness/response/order/yintai
* @author sunjing<sunjing@shopex.cn>
* @version $Id: abstract.php 2013-3-12 17:23Z
*/
abstract class apibusiness_response_order_yintai_abstract extends apibusiness_response_order_abstractbase
{
    /**
     * 
     *
     * @return void
     * @author
     **/
    protected function operationSel()
    {
        parent::operationSel();
        
    }

    /**
     * 是否接收订单
     *
     * @return void
     * @author
     **/
    protected function canAccept()
    {
        $result = parent::canAccept();
        if ($result === false) {
            return false;
        }

        # 未支付的款到发货订单拒收
        if ($this->_ordersdf['shipping']['is_cod'] != 'true' && $this->_ordersdf['pay_status'] == '0') {
            $this->_apiLog['info']['msg'] = '未支付订单不接收';
            return false;
        }

        return true;
    }

    /**
     * 需要更新的组件
     *
     * @return void
     * @author
     **/
    protected function get_update_components()
    {
        $components = array('markmemo','custommemo','marktype');

        if ($this->_tgOrder['createtime'] < strtotime('2014-01-24 10:30:00')) {
            $components[] = 'consignee';
        }

        return $components;
    }

    /**
     * 能够创建订单
     *
     * @return void
     * @author 
     **/
    public function canCreate()
    {

        if ($this->_ordersdf['status'] != 'active') {
            $this->_apiLog['info']['msg'] = ($this->_ordersdf['status'] == 'dead') ? '取消的订单不接收' : '完成的订单不接收';
            return false;
        }     

        return parent::canCreate();
    }

    /**
     * 对平台接收的数据纠正(有些是前端打的不对的)
     *
     * @return void
     * @author 
     **/
    protected function reTransSdf()
    {
        parent::reTransSdf();

        
    }

    
   
}