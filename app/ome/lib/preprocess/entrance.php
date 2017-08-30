<?php

class ome_preprocess_entrance {

    static private $__instance = array();

    static private $__methods_list = array('tbgift','invoice','2'=>'crm','outstorage');

    private $__use_method = null;

    public function setMethod($method){
        $method = strtolower($method);
        if(in_array($method,self::$__methods_list)){
            $this->__use_method = $method;
        }else{
            $this->__use_method = -1;
        }
        return $this;
    }

    public function process($params,&$msg){
        $orderIds = $this->_mergeGroup($params);
        $process_status = true;
        if($this->__use_method == -1){
            $msg = 'use error method';
            return false;
        }elseif($this->__use_method && $this->__use_method != -1){
            $obj = self::_instanceObj($this->__use_method);
            foreach ($orderIds as $orderId){
                if(!$obj->process($orderId,$flag_msg)){
                    $process_status = false;
                    $msg[$orderId][] = $flag_msg;
                }
            }
        }else{
            #应用类型暂时只有crm类型应用
            $app_type = channel_ctl_admin_channel::$appType;
            #检测crm节点有没有绑定
            $obj_channel = app::get('channel')->model('channel');
            $node_info = $obj_channel->getList('node_id',array('channel_type'=>$app_type['crm']));
            if(empty($node_info)||strlen($node_info[0]['node_id'])<=0){
                #如果crm应用没有绑定，则不需要执行crm相关流程
                unset(self::$__methods_list['2']);
            }
            foreach (self::$__methods_list as $method){
                $obj = self::_instanceObj($method);
                foreach ($orderIds as $orderId){
                    if(!$obj->process($orderId,$flag_msg)){
                        $process_status = false;
                        $msg[$orderId][] = $flag_msg;
                    }
                }
            }
        }

        if($process_status){
            return true;
        }else{
            return false;
        }
    }

    static private function _instanceObj($key){
        if(!isset(self::$__instance[$key])){
            self::$__instance[$key] = kernel::single(sprintf('ome_preprocess_%s',$key));
        }
        return self::$__instance[$key];
    }

    private function _mergeGroup($params) {
        $ids = array();
        if(is_array($params)){
            foreach ($params as $item) {
                $ids = array_merge($ids, $item['orders']);
            }
        }else{
            $ids = array($params);
        }
        return $ids;
    }

}
