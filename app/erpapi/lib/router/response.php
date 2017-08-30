<?php
/**
 * RESPONSE 路由
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_router_response
{
    /**
     * 渠道节点
     *
     * @var string
     **/
    private $__node_id;

    /**
     * 接口名，如:wms.delivery.status_update
     *
     * @var string
     **/
    private $__api_name;

    /**
     * 渠道ID
     *
     * @var string
     **/
    private $__channel_id;

    public function set_node_id($node_id)
    {
        $this->__node_id = $node_id;
        return $this;
    }

    public function set_channel_id($channel_id)
    {
        $this->__channel_id = $channel_id;
        return $this;
    }

    public function set_api_name($api_name)
    {
        $this->__api_name = $api_name;
        return $this;
    }

    public function dispatch($params,$sign_check=false)
    {
        try {
            // 节点和ID都不存在，抛出异常
            if (!$this->__node_id && !$this->__channel_id) throw new Exception("节点参数必填");         

            // 接口名不存在抛出异常
            if (!$this->__api_name) throw new Exception("接口名称必填");

            list($channel_type, $business, $method) = explode('.',$this->__api_name);
            if ($channel_type == 'ome') $channel_type = 'shop';

            // 实例化渠道类
            $channel_name = 'erpapi_channel_' . $channel_type;
            $channel_class = kernel::single($channel_name);

            if (!$channel_class instanceof erpapi_channel_abstract) throw new Exception("{$channel_name} not instanceof erpapi_channel_abstract");

            $channelRs = $channel_class->init($this->__node_id, $this->__channel_id);
            if (!$channelRs) throw new Exception("节点不存在");
            $adapter  = $channel_class->get_adapter();
            $platform = $channel_class->get_platform();

            // 配置文件
            if (in_array($adapter,array('matrix','openapi','prism')) && $sign_check) {
                // 默认
                $config_class = kernel::single('erpapi_'.$channel_type.'_config');

                // 如果有自身配置
                try {
                    if (class_exists('erpapi_'.$channel_type.'_'.$adapter.'_'.$platform.'_config')) {
                        $config_class = kernel::single('erpapi_'.$channel_type.'_'.$adapter.'_'.$platform.'_config');
                    }
                } catch (Exception $e) {
                    try {
                        if (class_exists('erpapi_'.$channel_type.'_'.$adapter)) {
                            $config_class = kernel::single('erpapi_'.$channel_type.'_'.$adapter.'_config');
                        }
                    } catch (Exception $e) {
                        
                    }
                }

                $config_class->init($channel_class);

                // 签名
                $sign = $params['sign']; unset($params['sign']);                
                $erp_sign = $config_class->gen_sign($params);

                if ($sign != $erp_sign) throw new Exception("签名错误");   
                

                if ($sign_check) return true;
            }

            // 默认数据转换类
            $object_class = kernel::single('erpapi_'.$channel_type.'_response_'.$business);

            $object_name_arr = array('erpapi',$channel_type,$adapter,$platform,'response',$business);

            $object_name = implode('_',array_filter($object_name_arr));
            try {
                if (class_exists($object_name)) {
                    $object_class = kernel::single($object_name);
                }
            } catch (Exception $e) {
                
            }
            $object_class->init($channel_class);

            // 数据转成标准格式
            $convert_params = $object_class->{$method}($params);

            // 写日志
            $apilogModel = app::get('ome')->model('api_log');
            $log_id = $apilogModel->gen_id();
            $logsdf = array(
                'log_id'        => $log_id,
                'task_name'     => $object_class->__apilog['title'],
                'status'        => ($result['rsp']=='succ' || $result['rsp']=='success') ? 'success' : 'fail',
                'worker'        => '',
                'params'        => '',
                'msg'           => $msg,
                'log_type'      => '',
                'api_type'      => 'response',
                'memo'          => '',
                'original_bn'   => $object_class->__apilog['original_bn'],
                'createtime'    => time(),
                'last_modified' => time(),
            );


            // 数据验证
            try {
                $params_name = 'erpapi_'.$channel_type.'_response_params_'.$business;
                if (class_exists($params_name)) {
                    $valid = kernel::single($params_name)->check($convert_params,$method);

                    if ($valid['rsp'] != 'succ') {
                        $logsdf['msg'] = '接收参数：'.var_export($params,true).'<hr/>转换后参数：'.var_export($convert_params,true).'<hr/>返回结果：'.$valid['msg'];
                        $logsdf['status'] = 'fail';

                        $apilogModel->insert($logsdf);

                        return array('rsp'=>'fail','msg'=>$valid['msg'],'msg_code'=>'','data'=>null);
                    }
                }

            } catch (Exception $e) {
                
            }

            // 最终的处理
            $result = kernel::single('erpapi_'.$channel_type.'_response_process_'.$business)->{$method}($convert_params);

            $logsdf['msg'] = '接收参数：'.var_export($params,true).'<hr/>转换后参数：'.var_export($convert_params,true).'<hr/>返回结果：'.var_export($result,true);
            $logsdf['status'] = ($result['rsp']=='succ' || $result['rsp']=='success') ? 'success' : 'fail';

            $apilogModel->insert($logsdf);

            if ($params['task'] && $result['rsp']=='succ') $apilogModel->set_repeat($params['task'],$log_id);

            if ($result['rsp'] != 'succ' && $result['rsp'] != 'success') {
                return $result;
            }

            return $result['data'];
        } catch (Exception $e) {
            // 写日志
            $apilogModel = app::get('ome')->model('api_log');
            $log_id = $apilogModel->gen_id();

            $msg = '请求参数：'.var_export($params,true).'<hr/>返回结果：'.$e->getMessage();
            $logsdf = array(
                'log_id'        => $log_id,
                'task_name'     => '错误异常',
                'status'        => 'fail',
                'worker'        => '',
                'params'        => '',
                'msg'           => $msg,
                'log_type'      => '',
                'api_type'      => 'response',
                'memo'          => '',
                'original_bn'   => '',
                'createtime'    => time(),
                'last_modified' => time(),
            );

            $apilogModel->insert($logsdf);

            return array('rsp'=>'fail','msg'=>$e->getMessage(),'msg_code'=>'','data'=>null);
        }
    }
}
