<?php
class console_event_trigger_goodssync{

    
    /**
     *
     * 商品同步通知创建发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 商品同步数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function create($wms_id, &$data, $sync = false){
        $db = kernel::database();
        $limit = 1000;
        $count = ceil(count($data)/$limit);
        for ($page=1;$page<=$count;$page++){
            $lim = ($page-1)*$limit;
            
            $params = array();
            $bns = array();
            for ($key=$lim;$key<$lim+$limit;$key++){
                
                if (!isset($data[$key])) break;
                $bns[] = '\''.$data[$key]['bn'].'\'';
                $params[] = $data[$key];
            }
            
            if ($params){
                //如果是自有仓储,直接更新成功
                $selfwms_list = kernel::single('console_goodssync')->get_wms_list('selfwms');
                
                if (in_array($wms_id,$selfwms_list)){
                    $new_tag = '1';
                    $sync_status = '3';
                    
                    $result = kernel::single('console_foreignsku')->batch_syncupdate($wms_id,$new_tag,$sync_status,$bns);
                }else{
                    $new_tag = '1';
                    $sync_status = '2';
                    $result = kernel::single('console_foreignsku')->batch_syncupdate($wms_id,$new_tag,$sync_status,$bns);
                }

                // $rs = kernel::single('middleware_wms_request', $wms_id)->goods_add($params,$sync);
                $rs = kernel::single('erpapi_router_request')->set('wms',$wms_id)->goods_add($params);

                unset($params);
               
            }
        }

        
    }

    /**
     *
     * 采购通知创建发起的响应接收方法
     * @param array $data
     */
    public function create_callback($res){

    }

    /**
     *
     * 商品同步通知更新发起方法
     * @param string $wms_id 仓库类型ID
     * @param array $data 商品同步数据信息
     * @param string $sync 是否同步请求，true为同步，false异步，默认异步
     */
    public function update($wms_id, &$data, $sync = false){
        $limit = 1000;
        $count = ceil(count($data)/$limit);
        ksort($data);
        for ($page=1;$page<=$count;$page++){
            $lim = ($page-1)*$limit;
            $params = array();
            $bns = array();
            for ($key=$lim;$key<$lim+$limit;$key++){
                if (!isset($data[$key])) break;
                $bns[] = '\''.$data[$key]['bn'].'\'';
                $params[] = $data[$key];
            }
            if ($params){
                
                

                //如果第三方仓储返回成功,则更新商品同步状态为已同步
                $selfwms_list = kernel::single('console_goodssync')->get_wms_list('selfwms');
               
                if (in_array($wms_id,$selfwms_list)){
                    $new_tag = '1';
                    $sync_status = '3';
                    kernel::single('console_foreignsku')->batch_syncupdate($wms_id,$new_tag,$sync_status,$bns);
                }else{
                    $new_tag = '1';
                    $sync_status = '2';
                    kernel::single('console_foreignsku')->batch_syncupdate($wms_id,$new_tag,$sync_status,$bns);
                }
                // $rs = kernel::single('middleware_wms_request', $wms_id)->goods_update($data,$sync);
                $rs = kernel::single('erpapi_router_request')->set('wms',$wms_id)->goods_update($data);

                unset($params);
            }
        }
        return true;
    }

    /**
     *
     * 商品通知更新发起的响应接收方法
     * @param array $data
     */
    public function update_callback($res){
        
    }

}