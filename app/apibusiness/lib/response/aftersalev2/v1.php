<?php
/**
* 退款单 版本一
*
* @category apibusiness
* @package apibusiness/response/refund
* @author chenping<chenping@shopex.cn>
* @version $Id: v1.php 2013-3-12 17:23Z
*/
class apibusiness_response_aftersalev2_v1 extends apibusiness_response_aftersalev2_abstract
{

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
     * 添加退款单
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        $this->_oldRefundsdf = $this->_refundsdf;
        $this->_apiLog['title']  = '前端店铺退款业务处理[订单：' . $this->_refundsdf['tid'].']';
        $this->_apiLog['info'][] = '接收参数：' . var_export($this->_oldRefundsdf, true);
        $this->_apiLog['info'][] = '前端店铺信息：'.var_export($this->_shop,true);
        $this->_apiLog['info'][] = '淘管接口版本：v'.$this->_tgver;
        
        $this->_refundsdf = $this->format_data($this->_refundsdf);

        
        $order_bn    = $this->_refundsdf['tid'];
        $shop_id     = $this->_shop['shop_id'];
        $shop_type = $this->_shop['shop_type'];
        // 订单号验证
        if (!$order_bn) {
            $this->_apiLog['info']['msg'] = 'no order bn';
            $this->exception(__METHOD__);
        }
        $orderModel = app::get(self::_APP_NAME)->model('orders');
        $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment,ship_status');
        
        if (!$tgOrder) {
            //根据店铺判断要获取订单的类型
            $order_type = ($this->_shop['business_type']=='fx') ? 'agent' : 'direct';
            // 淘管中无些单号订单，重新获取
            $orderRsp = kernel::single('apibusiness_router_request')->setShopId($shop_id)->get_order_detial($order_bn,$order_type);

            if ($orderRsp['rsp'] == 'succ') {
                $rs = kernel::single('ome_syncorder')->get_order_log($orderRsp['data']['trade'],$shop_id,$msg);
                if ($rs) {
                    $tgOrder = $orderModel->dump(array('order_bn'=>$order_bn,'shop_id'=>$shop_id),'pay_status,status,process_status,order_id,payed,cost_payment,ship_status');
                }
            }
        }
        if (!$tgOrder) {
            $this->_apiLog['info']['msg'] = 'no order in TAOGUAN';
            $this->exception(__METHOD__ , 'true');
        }
        
        
   }

    protected function format_data($sdf)
    {
        return $sdf;
    }
    /**
     * 更新退款单状态
     *
     * @return void
     * @author 
     **/
    public function status_update()
    {
        parent::status_update();

        $shop_id   = $this->_shop['shop_id'];
        $order_bn  = $this->_refundsdf['order_bn'];
        $refund_bn = $this->_refundsdf['refund_bn'];

        $refundModel = app::get(self::_APP_NAME)->model('refunds');

        $refund_detail = $refundModel->dump(array('refund_bn'=>$refund_bn,'shop_id'=>$shop_id));

        $order_id = $refund_detail['order_id'];
        
        $this->_updateOrder($order_id,$refund_detail['money']);
        
    }
}