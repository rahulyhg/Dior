<?php
class apibusiness_response_refund_yihaodian_v1 extends apibusiness_response_refund_v1{

    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        return parent::canAccept($tgOrder);
    }

    /**
     * 添加售后单
     *
     * @return void
     * @author 
     **/
    public function add()
    {
       $this->_apiLog['title']  = '前端店铺退款业务处理[订单：' . $this->_refundsdf['order_bn'].']';
       $this->_apiLog['info'][] = '接收参数：' . var_export($this->_refundsdf, true);
       $this->_apiLog['info']['msg'] = '退款单['.$this->_refundsdf['refund_bn'].']不走此接口';
        
       return true;
    }

    
}

?>