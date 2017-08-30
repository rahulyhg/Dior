<?php
/**
 * 订单业务同步请求
 * @author ome
 * @access public
 * @copyright www.shopex.cn 2010
 *
 */
class ome_rpc_request_version_2_order extends ome_rpc_request_version_base_order {

    /**
    * 订单编辑 iframe
    * @access public
    * @param Array $sdf 请求参数
    * @return Array
    */
    public function update_iframe($sdf){
        #判断是否发起请求
        if ($sdf['is_request'] !== true){
            return array('rsp'=>'success','msg'=>'','data'=>array('edit_type'=>'iframe'));
        }
        $order_bn = $sdf['order_bn'];
        if(!empty($order_bn)){
            $shop_id = $sdf['shop_id'];
            $notify_url = $sdf['ext']['notify_url'];
            $params = array(
                'tid' => trim($sdf['order_bn']),
                'notify_url' => base64_encode($notify_url)
            );
            $queue = false;

            if($shop_id){
                $title = '前端店铺('.$sdf['shop_name'].')订单编辑';
            }else{
                return false;
            }

            $log['log_title'] = $title;
            $log['original_bn'] = $order_bn;
            $log['status'] = 'success';
            $log['log_type'] = 'store.trade';
            $method = 'iframe.tradeEdit';
            $mode = 'sync';
            
            $rs = $this->request($method,$params,$callback,$title,$shop_id,5,$queue,$addon,$log,true,'GET');
                    
            return $rs;
        }else{
            return array('rsp'=>'fail','msg'=>'订单号不能为空');
        }
    }
    
    /**
     * 订单编辑 接口
     * @access public
     * @param Array $sdf 订单结构
     * @return boolean
     */
    public function update_order($sdf=''){
        return array('rsp'=>'success','msg'=>'新版本无需发起订单编辑');
    }

}