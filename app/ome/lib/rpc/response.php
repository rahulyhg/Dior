<?php
/**
 * RPC响应基类
 * @author shopex.cn ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_response {
    
    static $_shop_instance = array();

    function __construct(){
        if (defined('DEBUG') && DEBUG == true && function_exists('debug')){
            debug($_POST);
        }
    }

    /**
     * 接口过滤
     * @param Array $filter 过滤条件
     * @param string $col 返回字段
     * @return Array 店铺信息
     */
    function filter($filter=array()){
        if (empty($filter)){
            $node_id =  base_rpc_service::$node_id;
            $filter['node_id'] = $node_id;
        }else{
            $node_id = $filter['node_id'];
        }

        if (!isset(self::$_shop_instance[$node_id]) || empty(self::$_shop_instance[$node_id])){
            $shopObj = &app::get('ome')->model('shop');
            self::$_shop_instance[$node_id] = $shopObj->getRow($filter);
        }

        if(self::$_shop_instance[$node_id]){
            return self::$_shop_instance[$node_id];
        }else{
            // 行为日志
            $result['rsp'] = 'fail';
            $result['msg'] = 'node_id:'.$node_id.'对应的前端店铺没有找到';
            $log_addon['unique'] = $node_id;
            $this->action_log('','','未找到绑定的店铺信息','other',$result,$log_addon);

            return array('rsp'=>'fail','msg'=>'无法找到节点:'.$node_id.'对应的前端店铺');
        }
    }

    
    /**
    * 行为日志
    * @access public
    * @param String $class 接口名
    * @param String $method 方法名     
    * @param String $log_title 日志标题
    * @param String $log_type 日志类型          
    * @param Array $result 通信结果
    * @param Array $addon 附加参数
    */
    public function action_log($class,$method,$log_title,$log_type,$result,$addon = ''){
    
        $msg = isset($result['msg']) ? $result['msg'] : '';
        $log_status = isset($result['rsp']) ? $result['rsp'] : 'success';

        $api_filter = $addon;
        $oAction_log = &app::get('ome')->model('api_log');
        $api_detail = $oAction_log->dump($api_filter, 'log_id');
        if (empty($api_detail['log_id'])){
            $log_id = $oAction_log->gen_id();
           // $log_sdf = $oAction_log->write_log($log_title,$method,$params,$log_type,$log_status,$msg,$bn,$api_filter);
            $log_sdf = $oAction_log->write_log($log_id, $log_title, $class, $method, '', '', $log_type, $log_status, $logInfo, $addon);
            return $log_id;
        }
    }

    function send_user_error($code, $data){
        $res = array(
            'rsp'   =>  'fail',
            'res'   =>  $code,
            'data'  =>  $data,
        );
        echo json_encode($res);
    }
    
    /**
    * 获取店铺信息
    * @access public
    * @param Array $filter 过滤条件
    * @param String $col 返回字段
    * @return Array 店铺信息
    */
    function get_shop($filter=array(),$col='*'){
        if (empty($filter)){
            $filter['node_id'] = base_rpc_service::$node_id;
        }
        if (!isset(self::$_shop_instance[$node_id]) || empty(self::$_shop_instance[$node_id])){
            $shopObj = &app::get('ome')->model('shop');
            self::$_shop_instance[$node_id] = $shopObj->getRow($filter);
        }
        return self::$_shop_instance[$node_id];
    }

    
    function get_shop_id(&$responseObj){
        $shop = $this->get_shop("shop_id", $responseObj);
        return $shop['shop_id'];
    }
    

    
}