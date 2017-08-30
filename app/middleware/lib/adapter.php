<?php
/**
* 适配器类
*
* @copyright shopex.cn 2013.4.10
* @author dongqiujin<123517746@qq.com>
*/
class middleware_adapter{

    static $self_wms = array('selfwms');

    static $thirdparty_wms = array('ilcwms','matrixwms');

    /**
    * 判断渠道是否已绑定
    *
    * @access public
    * @param String $channel_id 渠道ID
    * @return bool
    */
    public function isBind($channel_id=''){
        return kernel::single('channel_func')->isBind($channel_id);
    }

    /**
    * 获取仓库所关联的WMS适配器
    *
    * @access public
    * @param String $branch_bn 仓库编号
    * @return String
    */
    public function getWmsByBranch($branch_bn=''){
        $wms_id = kernel::single('ome_branch')->getWmsId($branch_bn);
        return kernel::single('channel_func')->getAdapterByChannelId($wms_id);
    }

    /**
    * 根据wms_id获取WMS适配器
    *
    * @access public
    * @param String $wms_id
    * @return String
    */
    public function getWmsById($wms_id=''){
        return kernel::single('channel_func')->getAdapterByChannelId($wms_id);
    }

    /**
    * 根据node_id获取wms_id
    *
    * @access public
    * @param String $node_id
    * @return String
    */
    public function getWmsIdByNodeId($node_id=''){
        return kernel::single('channel_func')->getWmsIdByNodeId($node_id);
    }

    /**
    * 根据node_id获取adapter_type
    *
    * @access public
    * @param String $node_id
    * @return String
    */
    public function getAdapterTypeByNodeId($node_id=''){
        return kernel::single('channel_func')->getAdapterTypeByNodeId($node_id);
    }

    /**
    * 根据node_id获取adapter_flag
    *
    * @access public
    * @param String $node_id
    * @return String
    */
    public function getAdapterFlagByNodeId($node_id=''){
        return kernel::single('channel_func')->getAdapterByNodeId($node_id);
    }

    /**
    * 获取适配器sign密钥
    * @param String $node_id
    * @return String
    */
    public function getSignKey($node_id=''){
        return kernel::single('channel_func')->getSignKey($node_id);
    }

    /**
    * 根据wms_id获取node_id节点与
    *
    * @access public
    * @param String $wms_id
    * @return String
    */
    public function getNodeIdByWmsId($wms_id=''){
        return kernel::single('channel_func')->getNodeIdByChannelId($wms_id);
    }

    /**
    * 通过node_id获取渠道名称
    * @param String $node_id 节点号
    * @return String
    */
    public function getChannelNameByNodeId($node_id=''){
        return kernel::single('channel_func')->getChannelNameByNodeId($node_id);
    }

    /**
    * 获取接收适配器实例
    * @param String $adapter_type 适配器类型
    * @param String $node_id 节点号
    * @return Object
    */
    public function getResponseAdapter($adapter_type,$node_id=''){
        switch($adapter_type){
            case 'wms':
                $channel_id = $this->getWmsIdByNodeId($node_id);
                break;
            case 'shop':

                break;
        }
        try{
            $class_name = 'middleware_'.$adapter_type.'_response';
            if(class_exists($class_name) && $channel_id){
                return kernel::single($class_name,$channel_id);
            }
        }catch(Exception $e){
            return NULL;
        }
    }

    /**
    * 获取WMS适配器列表
    *
    * @access public
    * @return Array 适配器列表
    */
    public static function getWmsList(){

        $adapter_list = array();

        #读取所有适配器下的config文件
        $adapter_type_path = APP_DIR.'/middleware/lib/wms';
        if(!is_dir($adapter_type_path)){
            return NULL;
        }
        $handler = opendir($adapter_type_path);
        do{
            $file = readdir($handler);
            if(!$file) break;

            $adapter_dir = $adapter_type_path.'/'.$file;
            if(is_dir($adapter_dir) && !in_array($file,array('.','..'))){
                $adapter_class_name = 'middleware_wms_'.$file.'_func';
                if(middleware_wms_abstract::class_exists($adapter_class_name) && ((in_array($file, self::$self_wms) && app::get('wms')->is_installed()) || (in_array($file, self::$thirdparty_wms) && app::get('rpc')->is_installed()))){
                    $func_instance = kernel::single($adapter_class_name);
                    if(method_exists($func_instance,'getAdapter')){
                        if($func_instance->getAdapter()){
                            $adapter_list[] = $func_instance->getAdapter();
                        }
                    }
                }
            }
        }while(true);
        closedir($handler);

        return $adapter_list;
    }

}