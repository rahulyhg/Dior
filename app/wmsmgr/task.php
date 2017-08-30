<?php
class wmsmgr_task{

    function post_install($options){
        $channelObj = app::get('channel')->model('channel');
        $data = array(
                'channel_bn'=>'自有仓储',
                'channel_name'=>'自有仓储',
                'channel_type'=>'wms',
                'node_id'=>'selfwms',
                'node_type'=>'selfwms',
        );
        $result = $channelObj->save($data);
        if ($result) {
            $channel_id = $data['channel_id'];
            $adapterMdl = app::get('channel')->model('adapter');
            $adapter_sdf = array(
            'channel_id' => $channel_id,
            'adapter' => 'selfwms',
            );
            $adapterMdl->save($adapter_sdf);
            //查询是否有默认我的仓库
            $branch = $adapterMdl->db->selectrow("SELECT branch_id FROM sdb_ome_branch WHERE branch_bn='stockhouse' AND storage_code='A1' AND (wms_id is null or wms_id='')");
            if ($branch) {
                $adapterMdl->db->exec("UPDATE sdb_ome_branch SET wms_id=".$channel_id." WHERE branch_id=".$branch['branch_id']);
            }
        }
        
    }

}
