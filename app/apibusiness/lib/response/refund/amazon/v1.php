<?php
/**
* 退款单 版本一
*
* @category apibusiness
* @package apibusiness/response/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: v1.php 2013-3-12 17:23Z
*/
class apibusiness_response_refund_amazon_v1 extends apibusiness_response_refund__v1
{

    /**
     * 验证是否接收
     *
     * @return void
     * @author 
     **/
    protected function canAccept($tgOrder=array())
    {
        if ($this->_refundsdf['status'] == 'succ' || $this->_refundsdf['refund_type'] == 'refund') {
            if (bccomp($tgOrder['payed'], $this->_refundsdf['money'],3) < 0) {
                $this->_apiLog['info']['msg'] = '退款失败,支付金额('.$tgOrder['payed'].')小于退款金额('.$this->_refundsdf['money'].')';
                return false;        
            }
        }

        // 订单状态判断
        if ($tgOrder['process_status'] == 'cancel') {
            $this->_apiLog['info']['msg'] = '订单['.$this->_refundsdf['order_bn'].']已经取消，无法退款';
            return false;
        }

        return parent::canAccept($tgOrder);
    }


    /**
     * 添加退款单
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