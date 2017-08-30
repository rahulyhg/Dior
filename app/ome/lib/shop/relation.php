<?php
class ome_shop_relation{

    /**
     * 店铺绑定
     */
    public function bind($shop_id){
        //同步店铺支付方式
        $payFuncObj = kernel::single("ome_payment_func");
        if(method_exists($payFuncObj, 'sync_payments')){
            $payFuncObj->sync_payments($shop_id);
        }
        return true;
    }

    /**
     * 解除店铺绑定
     */
    public function unbind($shop_id){
        //删除店铺支付方式
        $payFuncObj = kernel::single("ome_payment_func");
        if(method_exists($payFuncObj, 'del_payments')){
            $payFuncObj->del_payments($shop_id);
        }

        //删除库存同步日志
        $stockLogObj = &app::get('ome')->model('api_stock_log');
        $stockLogObj->delete(array('shop_id'=>$shop_id));
        return true;
    }

    /**
     *
     * 指定某个前端店铺解除用户中心的绑定关系
     */
    public function unbindWithMatrix($to_nodeid){
        if(!$to_nodeid){ return false;}

        $url = MATRIX_RELATION_URL.'api.php';
        $token = base_certificate::token();
        $time_out = 10;

        $params = array(
            'app' => 'app.changeBindRelStatus',
            'from_node' => base_shopnode::node_id('ome'),
            'to_node' => $to_nodeid,
            'status' => 'del',
            'reason' => 'ome unbind this node',
        );

        $str ='';
        ksort($params);
        foreach($params as $key => $value){
            if($key != 'certi_ac' && $key != 'certificate_id'){
                $str .= $value;
            }
        }
        $signString = md5($str.$token);
        $params['certi_ac'] = $signString;

        $http = kernel::single('base_httpclient');
        $response = $http->set_timeout($time_out)->post($url,$params);
        if($response === HTTP_TIME_OUT){
            return false;
        }else{
            $result = json_decode($response,true);
            return $result;
        }
    }

    public function getBindInfosFromMatrix(){
        $url = MATRIX_RELATION_URL.'api.php';

        $token = '01c072a86b84a3a580d2e2b06ac58a87';
        $time_out = 10;

        $params = array(
            'app' => 'matrix.syncNodeRelation',
            'from_node' => base_shopnode::node_id('ome'),
            'from_node_type' => 'ecos.ome',
            'type' => 'all',
            'status' => 'true',
        );

	    $str ='';
        ksort($params);
        foreach($params as $key => $value){
            if($key != 'certi_ac' && $key != 'certificate_id'){
                $str .= $value;
            }
        }
        $signString = md5($str.$token);
	    $params['certi_ac'] = $signString;

        $http = kernel::single('base_httpclient');
        $response = $http->set_timeout($time_out)->post($url,$params);

        if($response === HTTP_TIME_OUT){
            return false;
        }else{
            $result = json_decode($response,true);
            return $result;
        }
    }
}