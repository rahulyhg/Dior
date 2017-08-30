<?php
class ome_session{
    public function updateSession(){
        $certi = base_certificate::get('certificate_id');
        $nodeid = base_shopnode::node_id('ome');
        $token = base_certificate::get('token');
        //$token = '213beec1d7aa0cfe6a2da0ae75f02e2c382e56ded768adb265841dd07d545c69';
        //$url = 'http://service.ex-sandbox.com/';  // 沙箱地址
        $url = 'http://service.shopex.cn/';     //  线上地址

        //echo $certi.":certi<br />";
        //echo $nodeid.":nodeid<br />";
        //echo $token.":token<br />";

        $params['method']      = 'eco.getTbSession';
        //$params['nodeid']      = '1838373339';
        $params['nodeid']      = $nodeid;
        $params['timestamp']   = time();//;
        $params['v']      = '2.0';
        $params['format'] = 'json';

        $http = kernel::single('base_httpclient');
        $http->timeout = 6;


        //echo "<pre>";
        $shopObj = &app::get('ome')->model('shop');
        $shopList = $shopObj->getList('*');
        foreach($shopList as $key=>$val){
        	if(empty($val['node_id']) || empty($val['node_type']))continue;
        	
            //$val['addon'] = json_decode($val['addon'],1);
            $params['nickname'] = urlencode($val['addon']['nickname']);
            $params['sign'] = $this->make_sign($params,$token);

            //print_r($params);

            $result = $http->post($url,$params);
            $result = json_decode($result,1);

            //print_r($result);

            if($result['res']=='succ'){
                $addon = array('session'=>$result['info']['tb_session'], 'nickname'=>urldecode($result['info']['nickname']));
                $data = array('addon'=>$addon);
                $filter = array('shop_id'=>$val['shop_id']);

                //print_r($data);
                $logStr = $val['name'].'的addon：'.json_encode($val['addon']).'更新为：'.json_encode($addon);
                $this->ilog($logStr);
                $shopObj->update($data, $filter);
            }
            unset($params['nickname'],$params['sign'],$result);
        }
    }

    function make_sign($params,$secret){
        $params['nickname'] = urldecode($params['nickname']);
        $tmp_params = $params;
        unset($tmp_params['sign']);
        ksort($tmp_params);
        $sign = '';
        foreach($tmp_params as $k => $v){
            $sign .= $k . $v;
        }
//        echo '<br/>';
//        echo $sign;
//        echo '<br/>';
        $return = strtoupper(md5(strtoupper(md5($sign)).$secret));
        return $return;
    }

    function ilog($str){
        $filename = ROOT_DIR . '/script/update/logs/update_session_' . date('Y-m-d') . '.log';
        $fp = fopen($filename, 'a');
        fwrite($fp, date("m-d H:i") . "\t" . $str . "\n");
        fclose($fp);
    }
}