<?php
class ome_return_product{
    
    /**
     * 批量更新状态
     * @param   status_type return_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function batch_update($status_type,$return_id){
        set_time_limit(0);
        $need_return_id = array();
        foreach ($return_id as $return_id ) {
            $return_id = explode('||',$return_id);
            $need_return_id[] = $return_id[1];
        }
        $oReturn = &app::get('ome')->model('return_product');
        $sql = 'SElECT shop_id,shop_type,source,return_id,return_bn FROM sdb_ome_return_product WHERE return_id in ('.implode(',',$need_return_id).') AND `status` in (\'1\',\'2\')';

        $return_list = $oReturn->db->select($sql);
        
        $error_msg = array();
        if ($status_type == 'agree') {
            foreach ( $return_list as $return ) {
                $return_id = $return['return_id'];
                $rs = array();
                $adata = array(
                    'choose_type_flag'=>'1',
                    'status'=>'3',
                    'return_id'=>$return_id,
                );
                if ((in_array($return['shop_type'],array('taobao','tmall','meilishuo'))) && $return['source'] == 'matrix') {
                    $mod = 'async';
                    if ($return['shop_type'] == 'tmall' || $return['shop_type']== 'meilishuo') {
                        $api = TRUE;
                        $adata['choose_type_flag'] = '0';
                        $mod = 'sync';
                    }
                    $rs = kernel::single('ome_service_aftersale')->update_status($return_id,'3',$mod);
                    
                }
                if ($rs && $rs['rsp'] == 'fail') {
                    $fail++;
                    $error_msg[] = '单号:'.$return['return_bn'].",".$rs['msg'];
                }else{
                    $oReturn->tosave($adata,$api);
                }
            }
        }elseif($status_type == 'refuse') {
            $batchList = kernel::single('ome_refund_apply')->return_batch('refuse_return');
            
            foreach ( $return_list as $return ) {
                $return_id = $return['return_id'];
                $rs = array();
                $adata = array(
                    'status'=>'5',
                    'return_id'=>$return_id,
                );
                if ((in_array($return['shop_type'],array('taobao','tmall'))) && $return['source'] == 'matrix') {
                    $return_batch = $batchList[$return['shop_id']];
                    $picurl = $return_batch['picurl'];
                    if ( $return['shop_type'] == 'tmall' ) {
                        $picurl = file_get_contents($picurl);
                        $picurl = base64_encode($picurl);
                    }
                    $memo = array(
                       'refuse_message'=>$return_batch['memo'],
                        'refuse_proof'=>$picurl,
                        'imgext'=>$return_batch['imgext'],
                    );
                    $rs = kernel::single('ome_service_aftersale')->update_status($return_id,'5','sync',$memo);
                }
                if ($rs && $rs['rsp'] == 'fail') {
                    $fail++;
                    $error_msg[] = '单号:'.$return['return_bn'].",".$rs['msg'];
                }else{
                    $oReturn->tosave($adata,$api);
                }
            }
        }
        
        $result = array('error_msg'=>$error_msg,'fail'=>$fail);
        
        return $result;
    }
    
    /**
    * 获取可操作列表
    *
    */
    function return_list($return_id){
        set_time_limit(0);
        $oReturn = &app::get('ome')->model('return_product');
        $sql = 'SElECT return_id FROM sdb_ome_return_product WHERE return_id in ('.implode(',',$return_id).') AND `status` in (\'1\',\'2\')';

        $return_list = $oReturn->db->select($sql);
        $need_return = array();
        foreach ( $return_list as $return ) {
            $need_return[] = $return['return_id'];
        }
        
        return $need_return;
    }
}
