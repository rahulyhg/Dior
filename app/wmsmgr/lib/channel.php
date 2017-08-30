<?php
/**
* callback回调
*
*/
class wmsmgr_channel{

    /**
    * 绑定关系回调
    * 
    */
    public function bindCallback($result){
        $channel_id = $result['channel_id'];
        $nodes = $_POST;
        $status = $nodes['status'];
        $node_id = $nodes['node_id'];
        $node_type = $nodes['node_type'];
        $api_v = $nodes['api_v'];
        $filter = array('channel_id'=>$channel_id);
        
        $Obj_channel = kernel::single('channel_channel');
        $shopdetail = $Obj_channel->dump(array('node_id'=>$node_id), 'node_id');
        if ($status=='bind' and !$shopdetail['node_id']){
            if ($node_id){
                #绑定
                $Obj_channel->bind($node_id,$node_type,$filter);
                
                #更新
                $data = array('api_version'=>$api_v,'addon'=>$nodes);
                $Obj_channel->update($data, $filter);

                die('1');
            }
        }elseif ($status=='unbind'){
            $Obj_channel->unbind($filter);
            die('1');
        }
        die('0');
    }

}
