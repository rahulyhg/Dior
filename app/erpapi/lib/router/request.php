<?php
/**
 * 请求转发路由
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
@include(dirname(__FILE__).'/../apiname.php');

class erpapi_router_request
{
    /**
     * 平台类型  wms|shop
     *
     * @var string
     **/
    private $__channel_type = '';

    /**
     * 平台ID
     *
     * @var string
     **/
    private $__channel_id = null;

    /**
     * 标识业务 delivery|goods ...
     *
     * @var string
     **/
    private $__business;


    /**
     * 设置初始化
     *
     * @return void
     * @author 
     **/
    public function set($channel_type,$channel_id)
    {
        $this->__channel_type = $channel_type;
        
        $this->__channel_id   = $channel_id;
        
        // $this->__business      = $business;

        return $this;
    }

    /**
     * 
     *
     * @return Array array('rsp'=>'succ|fail','msg'=>'','data'=>'','msg_code'=>'')
     * @author 
     **/
    public function __call($method,$args)
    {   
        try {
            if (!$this->__channel_id) throw new Exception("channel_id is required");

            if (!$this->__channel_type) throw new Exception("channel_type is required");

            list($this->__business,$action) = explode('_',$method);
            if (!$this->__business || !$action) {
                throw new Exception('method:format error', 1);
            }

            $channel_name = 'erpapi_channel_'.$this->__channel_type;
            $channel_class = kernel::single($channel_name);
            if (!$channel_class instanceof erpapi_channel_abstract) throw new Exception("{$channel_name} not instanceof erpapi_channel_abstract");

            $channelRs = $channel_class->init(null,$this->__channel_id);
            if (!$channelRs) throw new Exception('渠道不存在');

            $adapter  = $channel_class->get_adapter();
            $platform = $channel_class->get_platform();
            $ver      = $channel_class->get_ver();
            $ver      = version_compare($ver, '1' ,'>') ? $ver : '';

            // 可配置默认类
            $default_config_name = 'erpapi_'.$this->__channel_type.'_config';
            $config_class = kernel::single($default_config_name);

            try {
                // 自带配置类
                $config_name_arr = array('erpapi',$this->__channel_type,$adapter,$platform,'config');
                $config_name = implode('_',array_filter($config_name_arr));

                if (class_exists($config_name)) {
                    $config_class = kernel::single($config_name);

                    if (!is_subclass_of($config_class, $default_config_name)) throw new Exception("{$config_name} not instanceof {$default_config_name}");
                }    
            } catch (Exception $e) {
                try {
                    $config_name_arr = array('erpapi',$this->__channel_type,$adapter,'config');
                    $config_name = implode('_',array_filter($config_name_arr));

                    if (class_exists($config_name)) {
                        $config_class = kernel::single($config_name);
                        if (!is_subclass_of($config_class, $default_config_name)) throw new Exception("{$config_name} not instanceof {$default_config_name}");
                    }
                } catch (Exception $e) {
                       
                }   
            }
            $config_class->init($channel_class);

            // 结果默认类
            $result_class = kernel::single('erpapi_result');

            try {
                // 自带结果类
                $result_name_arr = array('erpapi',$this->__channel_type,$adapter,$platform,'result');
                $result_name = implode('_',array_filter($result_name_arr));

                if (class_exists($result_name)) {
                    $result_class = kernel::single($result_name);

                    if (!is_subclass_of($result_class, 'erpapi_result')) throw new Exception("{$result_name} not instanceof erpapi_result");
                }
            } catch (Exception $e) {
                
            }

            // 平台处理默认类
            $default_object_name = 'erpapi_'.$this->__channel_type.'_request_'.$this->__business;

            $object_class = kernel::single($default_object_name);
            try {
                // 自带处理类
                $object_name_arr = array('erpapi',$this->__channel_type,$adapter,$platform,'request',$this->__business);
                $object_name = implode('_',array_filter($object_name_arr));

                if (class_exists($object_name)) {
                    $object_class = kernel::single($object_name);

                    if ( !(!$adapter && $platform=='selfwms') && !is_subclass_of($object_class, $default_object_name)) throw new Exception("{$object_name} not instanceof {$default_object_name}");

                }
            } catch (Exception $e) {
                
            }
            $object_class->init($channel_class,$config_class,$result_class);

            if (!method_exists($object_class,$method)) throw new Exception("method error");

            return call_user_func_array(array($object_class,$method), $args);
        } catch (Exception $e) {
            return array('rsp'=>'fail','msg'=>$e->getMessage(),'data'=>'','msg_code'=>'');
        }
    }
}