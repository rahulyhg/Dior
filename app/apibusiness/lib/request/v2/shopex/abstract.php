<?php
/**
* shopex体系，抽象类版本二
*
* @category apibusiness
* @package apibusiness/lib/request/v2
* @author chenping<chenping@shopex.cn>
* @version $Id: abstract.php 2013-13-12 14:44Z
*/
abstract class apibusiness_request_v2_shopex_abstract extends apibusiness_request_shopexabstract
{
    /**
     * 订单编辑 iframe
     *
     * @return MIX
     * @author 
     **/
    public function update_iframe($order,$is_request=true,$ext=array())
    {
        // 判断是否发请求
        if ($is_request != true) {
            $edit_type = $order['source'] == 'matrix' ? 'iframe' : 'local';
            return array('rsp'=>'success','msg'=>'','data'=>array('edit_type'=>$edit_type));
        }

        $order_bn   = trim($order['order_bn']);
        $shop_id    = $order['shop_id'];
        $notify_url = $ext['notify_url'];
        $param = array(
            'tid'        => $order_bn,
            'notify_url' => base64_encode($notify_url),
        );

        $title = '前端店铺('.$this->_shop['name'].')订单编辑';

        $log = array(
            'log_title'   => $title,
            'original_bn' => $order_bn,
            'status'      => 'success',
            'log_type'    => 'store.trade', 
        );

        return $this->_caller->request(IFRAME_TRADE_EDIT_RPC,$param,array(),$title,$shop_id,5,false,'',$log,true,'GET');

    }// TODO TEST

    /**
     * 更新订单
     *  
     * @param Array $order 订单主表信息
     * @return MIX
     * @author 
     **/
    public function update_order($order)
    {
        return array('rsp'=>'success','msg'=>'新版本无需发起订单编辑');
    }// TODO TEST
}