<?php
/**
 * 支付方式同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_paymethod extends ome_rpc_request {

    /**
     * 同步店铺支付方式
     * @access public
     * @param int $shop_id 店铺ID
     * @return boolean
     */
    public function getPayment($shop_id){
        if(!empty($shop_id)){
            $shopObj = &app::get('ome')->model('shop');

            $shop = $shopObj->dump($shop_id);
            $c2c_shop_list = ome_shop_type::shop_list();

            if ($shop['shop_type'] && !in_array($shop['shop_type'],$c2c_shop_list)){
                $params = array();
                $callback = array(
                    'class' => 'ome_rpc_request_paymethod',
                    'method' => 'getPayment_callback',
                );

                $title = '同步店铺('.$shop['name'].')的支付方式';
                $api_name = 'store.shop.payment_type.list.get';

                $this->request($api_name,$params,$callback,$title,$shop_id);
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    function getPayment_callback($result){

        $status = $result->get_status();
        if($status == 'succ'){
            $cfgObj = &app::get('ome')->model('payment_cfg');
            $payShopObj = &app::get('ome')->model('payment_shop');
            $apiLogObj = &app::get('ome')->model('api_log');
            $shopObj = &app::get('ome')->model('shop');
            
            $msg_id = $result->get_msg_id();
            $callback_params = $result->get_callback_params();
            $request_params = $result->get_request_params();
            
            $shop_id = $callback_params['shop_id'];
            $log_id = $callback_params['log_id'];
            //$apilog_detail = $apiLogObj->dump(array('log_id'=>$log_id), 'params');
            //$apilog_detail = unserialize($apilog_detail['params']);
            
            //$apilog_detail = $request_params;
            //$node_id = $apilog_detail[1]['to_node_id'];
            //$shop = $shopObj->dump(array('node_id'=>$node_id));
            //$shop_id = $shop['shop_id'];
            
            $data = $result->get_data();
            $rsp = $result->get_status();
            $payments = $data;

            if( (is_array($payments) && count($payments)>0 && $shop_id) || ($rsp == 'succ') ) {
                $pay_bn = '';
                foreach((array)$payments as $payment){
                    $pay_bn = $payment['pay_bn'];
                    if(isset($pay_bn) && $pay_bn){
                        $pay_bns[] = $payment['pay_bn'];
                        $pay_type = $payment['pay_type'];

                        $payShopObj->delete(array('pay_bn'=>$pay_bn,'shop_id'=>$shop_id));
                        $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn), 'pay_bn,shop_id');
                        if(!isset($payShop['shop_id']) && !$payment['shop_id']){
                            $cfgObj->delete(array('pay_bn'=>$pay_bn));
                        }

                        $cfgSdf = array(
                            'custom_name' => $payment['custom_name'],
                            'pay_bn' => $pay_bn,
                            'pay_type' => $pay_type,
                        );
                        $payShopSdf = array(
                            'pay_bn' => $pay_bn,
                            'shop_id' => $shop_id,
                        );
                        $cfgObj->insert($cfgSdf);
                        $payShopObj->insert($payShopSdf);
                    }
                }

                $payShops = $payShopObj->getList('*',array('shop_id'=>$shop_id));
                $pay_bn = '';
                foreach($payShops as $payShop){
                    $pay_bn = $payShop['pay_bn'];
                    if($pay_bn && !in_array($pay_bn,$pay_bns)){
                        $payShopObj->delete(array('pay_bn'=>$pay_bn,'shop_id'=>$shop_id));
                        $payShop = $payShopObj->dump(array('pay_bn'=>$pay_bn), 'pay_bn,shop_id');
                        if(!isset($payShop['shop_id']) && !$payment['shop_id']){
                            $cfgObj->delete(array('pay_bn'=>$pay_bn));
                        }
                    }
                }
                return $this->callback($result);
            }else{
                $msg = 'fail' . ome_api_func::api_code2msg('re001', '', 'public');
                $apiLogObj->update_log($log_id, $msg, 'fail');
                return array('rsp'=>'fail', 'res'=>$msg, 'msg_id'=>$msg_id);
            }
        }
        return $this->callback($result);
    }
}