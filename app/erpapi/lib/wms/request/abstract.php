<?php
/**
 * ABSTRACT
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
abstract class erpapi_wms_request_abstract
{
    /**
     * 渠道
     *
     * @var string
     **/
    protected $__channelObj;

    final public function init(erpapi_channel_abstract $channel, erpapi_config $config, erpapi_result $result)
    {
        $this->__channelObj = $channel;

        // 默认以JSON格式返回
        $this->__caller = kernel::single('erpapi_caller')
                            ->set_config($config)
                            ->set_result($result);
    }

    /**
     * 成功输出
     *
     * @return void
     * @author 
     **/
    final public function succ($msg='', $msgcode='', $data=null)
    {
        return array('rsp'=>'success', 'msg'=>$msg, 'msg_code'=>$msgcode, 'data'=>$data);
    }

    /**
     * 失败输出
     *
     * @return void
     * @author 
     **/
    final public function error($msg, $msgcode, $data=null)
    {
        return array('rsp'=>'fail','msg'=>$msg,'msg_code'=>$msgcode,'data'=>$data);
    }

    /**
     * 生成唯一键
     *
     * @return void
     * @author 
     **/
    final public function uniqid(){
        $microtime  = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval    = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    /**
     * 获取仓库售达方
     *
     * @return void
     * @author 
     **/
    final public function get_warehouse_code($branch_bn)
    {
        $branch_relationObj = &app::get('wmsmgr')->model('branch_relation');
        $branch_relation = $branch_relationObj->dump(array('sys_branch_bn'=>$branch_bn));

        return $branch_relation['wms_branch_bn'] ? $branch_relation['wms_branch_bn'] : $branch_bn;
    }

    /**
     * 获取物流公司售达方
     *
     * @return void
     * @author 
     **/
    final public function get_wmslogi_code($wms_id,$logi_code)
    {
        $logistics_code = kernel::single('wmsmgr_func')->getWmslogiCode($wms_id,$logi_code);

        return $logistics_code ? $logistics_code : $logi_code;
    }

    /**
     * 回调
     *
     * @return void
     * @author 
     **/
    public function callback($response, $callback_params)
    {

        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $status = 'fail'; $msg = $err_msg.'('.$res.')';
        if ($rsp == 'succ') {
            $msg = '成功';
            $status = 'success';    
        }

        // 记录失败
        $obj_type = $callback_params['obj_type'];
        $obj_bn   = $callback_params['obj_bn'];
        $method   = $callback_params['method'];
        $log_id   = $callback_params['log_id'];

        $failApiModel = app::get('erpapi')->model('api_fail');
        $failApiModel->publish_api_fail($method,$callback_params,$response);

        if ($log_id) {
            $logModel = app::get('ome')->model('api_log');
            $logModel->update_log($log_id, $msg, $status, null, null);
        }

        return array('rsp'=>$rsp,'res'=>'', 'msg'=>$msg, 'msg_code'=>$msg_code, 'data'=>$data);
    }

    final protected function _formate_receiver_citye($receiver_city)
    {
        $zhixiashi = array('北京','上海','天津','重庆');
        $zizhiqu = array('内蒙古','宁夏回族','新疆维吾尔','西藏','广西壮族');
        $zxdata = array();
        $zzq = array();
        $prov = array();
        
        preg_match('/(.*?)市/',$receiver_city,$zxdata);///^def/
        preg_match('/(.*?)自治区/',$receiver_city,$zzq);
        preg_match('/(.*?)省/',$receiver_city,$prov);

        if (!$zxdata && in_array($receiver_city,$zhixiashi)) {
           $receiver_city = $receiver_city.'市';
        }else if (!$zzq && in_array($receiver_city,$zizhiqu)) {
            $receiver_city = $receiver_city.'自治区';
        }elseif(!$prov){
            $receiver_city = $receiver_city.'省';
        }
        return $receiver_city;
    }

    protected function transfer_inventory_type($type_id)
    {
        $inventory_type = array(
            '5'   => '101',//残次品
            '50'  => '101',
            '300' => '401',//样品
            '400' => '501',//新品
        );

        return isset($inventory_type[$type_id]) ? $inventory_type[$type_id] : '1';
    }
}