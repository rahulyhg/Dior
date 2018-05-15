<?php
class qmwms_request_abstract{

    private $param  = array(
      // 按接口提供的逐一填写
      "customerId"     => 'LVMH_FSH_OMS',
      "format"         => 'xml',
      "sign_method"    => 'md5',
      "v"              => '2.4',
);

    /**
     * 签名
     * @param $secret  安全码
     * @param $param   提交参数
     * @param $body     提交文档内容
     */
    public function sign($secret, $param, $body) {
        if ( empty($body) ) {
            exit('Body can\'t empty!');
        }

        ksort($param);
        $outputStr = '';
        foreach ($param as $k => &$v) {
            if ( empty($v) ) {
                exit('Param can\'t error!');
            }
            $outputStr .= $k . $v;
        }
        $outputStr = $secret . $outputStr . $body . $secret;
        return strtoupper(md5($outputStr));
    }

    // 业务逻辑
    public function request($body,$method) {
        $qmwmsApi = app::get('qmwms')->model('qmwms_api');
        $apiData = $qmwmsApi->getList('*',array(),0,1);
        $apiParam = unserialize($apiData[0]['api_params']);
        $wms_url = $apiParam['wms_api'];
        $secret  = $apiParam['app_secret'];

        $this->param['app_key']   = $apiParam['app_key'];
        $this->param['method']    = $method;// 调用方法
        $this->param['timestamp'] = date("Y-m-d H:i:s");// 时间
        $this->param['sign']      = $this->sign($secret, $this->param , $body);// 签名
        $url    = $wms_url."?". http_build_query($this->param);
        $return = $this->httpCurl($url, $body);
        //echo "<pre>";print_r($return);exit;
        return $return;
    }

    /**
     * 请求数据
     * @param $url             请求地址
     * @param $data        提交数据
     * @param $requestType  请求类型
     */
    public function httpCurl($url, $data) {
        //print($url);
        //初始化curl
        $ch = curl_init();
        //执行超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        //连接超时时间
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);

        curl_setopt($ch, CURLOPT_URL, $url);
        //是否启用SSL验证
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);//使用的ssl版本
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $uheader = array(
            'content-type:text/xml',
        );
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $uheader);
        //将curl_exec()获取的信息以文件流的形式返回
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt ($ch, CURLOPT_POST, true );
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $data );
        //var_dump(curl_error($ch));
        //var_dump(curl_getinfo($ch));
        $return = curl_exec($ch);
        //error_log(date('Y-m-d H:i:s').'返回:'."\r\n".var_export($return,true)."\r\n", 3, ROOT_DIR.'/data/logs/wmsrequest'.date('Y-m-d').'.xml');
        curl_close($ch);
        return $return;
    } 

}