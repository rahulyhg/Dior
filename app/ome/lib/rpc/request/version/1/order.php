<?php
/**
 * 订单业务同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_version_1_order extends ome_rpc_request_version_base_order {

    /**
    * 订单编辑 iframe
    * @access public
    * @param Array $params 请求参数
    * @return Array
    */
    public function update_iframe($params){
        $data = array('edit_type'=>'local');
        return array('rsp'=>'success','msg'=>'本地订单编辑','data'=>$data);
    }
}