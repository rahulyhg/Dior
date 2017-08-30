<?php
class channel_rpc_response_crm extends ome_rpc_response{
    public function crm_callback($result){
        $channel_id = $result['channel_id'];
        $nodes = $_POST;
        $status = $nodes['status'];
        $node_id = $nodes['node_id'];
        $node_type = $nodes['node_type'];
        $api_v = $nodes['api_v'];
        $filter = array('channel_id'=>$channel_id);
        
        $Obj_channel = &app::get('ome')->model('channel');
        $shopdetail = $Obj_channel->dump(array('node_id'=>$node_id), 'node_id');
        if ($status=='bind' and !$shopdetail['node_id']){
            if ($node_id){
                $data = array('api_version'=>$api_v,'node_id'=>$node_id,'node_type'=>$node_type,'addon'=>$nodes);

                $Obj_channel->update($data, $filter);

                die('1');
            }
        }elseif ($status=='unbind'){
            $data = array('node_id'=>'');
            $Obj_channel->update($data, $filter);
            die('1');
        }
        die('0');
        
    }
    /**
     * 获取中心通知的淘宝session过期的时间
     *  
     * @param  void
     * @return void
     * @author 
     **/
/*     public function crm_session($data){ 
        
        $data = $_POST;
        $certi_ac = $data['certi_ac'];
        unset($data['certi_ac']);
        $token = base_certificate::get('token');
        $sign = $this->genSign($data,$token);
        if($certi_ac != $sign){
           echo json_encode(array('res'=>'fail','msg'=>'签名错误'));
           exit;
        }
        $filter = array('node_id'=>$data['to_node']);
        $session_expire_time = $data['session_expire_time'];
        $shopMdl  = app::get('ome')->model('shop');
        $shopinfo = $shopMdl->getList('addon',$filter);
        
        if(is_array($shopinfo) && count($shopinfo)>0){
            if ($addon = $shopinfo[0]['addon'] ) {
                $newaddon['addon'] = array_merge($addon,$data);
            }
            $shopMdl->update($newaddon,$filter);
            echo json_encode(array('res'=>'succ','msg'=>''));
        }else{
            echo json_encode(array('res'=>'fail','msg'=>'没有找到网店'));
        }
        exit;
    }
    
    public function genSign($params,$token){
        ksort($params);
        $str = '';
        foreach ($params as $key =>$value) {
         
              if ($key != 'certi_ac' && $key != 'certificate_id') {
                 $str .= $value;
              }
        }
        $signString = md5($str.$token);
        return $signString;
    } */
}
