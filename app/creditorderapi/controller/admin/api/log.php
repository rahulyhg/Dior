<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 10:15
 */
class creditorderapi_ctl_admin_api_log extends desktop_controller
{
    public  $http_code='';
    function index()
    {
        $base_filter = array();
        $params = array(
            'title' => '积分订单api日志列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_filter' => true,
            'base_filter' => $base_filter,
            'use_buildin_recycle'=>false,
            'actions' => array(
                array(
                    'label'=>'重新发起请求',
                    'submit'=>'index.php?app=creditorderapi&ctl=admin_api_log&act=re_requst',
                    //'target'=>'refresh'
                    ),
                
            ),
        );
        $this->finder('creditorderapi_mdl_api_log', $params);
    }
    //日志重推机制
    function re_requst(){
        if(empty($_POST['id'])){
            echo '<script>alert("请选择一条记录")</script>';exit;
        }
        $this->begin('index.php?app=creditorderapi&ctl=admin_api_log&act=index');
        $apiLogid = $_POST['id'];
        
        $apiMdl = $this->app->model('api_log');
        foreach($_POST['id'] as $id){
            $apiInfo = $apiMdl->getList('*',array('id'=>$id));
            $api_bn  =$apiInfo['0']['api_bn'];
            if(($apiInfo['0']['api_status']=='fail')&&($apiInfo['0']['http_method']=='site.creditorderapi.site.update.order.status')){
                $data = $apiInfo['0']['http_request_data'];
                $url = $apiInfo['0']['http_url'];
                $method = $apiInfo['0']['http_method'];
                $response_data = $this->action($url,$data);
                $result = json_decode(json_encode(simplexml_load_string($response_data)), true);
                //echo '<pre>d1';print_r($result);exit;
                if($this->http_code=='200'){
                    if($result['StatusCode'] != '000'){
                        $updateArr = array(
                                    'id'=>$id,
                                    'api_status'=>'fail',
                                    'http_response_code'=>$this->http_code,
                                    'api_request_time'=>time(),
                                    'http_response_data'=> $result,
                                );
                    }else{
                        $updateArr = array(
                                    'id'=>$id,
                                    'api_status'=>'success',
                                    'http_response_code'=>$this->http_code,
                                    'api_request_time'=>time(),
                                    'http_response_data'=> $result,
                                );
                    } 
                    $apiMdl->save($updateArr);
                    continue;
                }else{
                    $msg = "单据号".$api_bn."重新请求失败：".$result['message'];
                    $updateArr = array(
                                        'id'=>$id,
                                        'api_status'=>'fail',
                                        'http_response_code'=>$this->http_code,
                                        'api_request_time'=>time(),
                                        'http_response_data'=> $result,
                                    );
                    $apiMdl->save($updateArr);
                    continue;
                }
            }else{
                //$msg = "单据号".$api_bn."不需要重新请求";
                //$this->end(false,$this->app->_($msg));
            }
        }
        $this->end(true,'操作完成');
    }

    public function action($url, $data)
    {   //echo '<pre>d;';print_r($url);print_r($data);exit;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $httpStatusCode = '100';
        //while ($httpStatusCode != 200 && $retry--) {
        $output = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //}
        $this->http_code = $httpStatusCode;
        
        curl_close($ch);
        return $output;
    }
}
