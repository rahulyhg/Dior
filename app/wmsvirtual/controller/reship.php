<?php
class wmsvirtual_ctl_reship extends desktop_controller{

    
    function index()
    {
        $branchObj = &app::get('ome')->model('branch');
        $node_type = array('jd_wms','ilc');
         $wms_id = &app::get('channel')->model('channel')->get_wmd_idBynodetype($node_type);

         $branchs = $branchObj->getList('branch_id',array('wms_id'=>$wms_id),0,-1);
         $base_filter['is_check'] = '1';
         $branch_ids = array();
         foreach ($branchs as $branch ) {
             $branch_ids[] = $branch['branch_id'];
         }
         if ($branch_ids){
            $base_filter['branch_id'] = $branch_ids;
        }else{
            $base_filter['branch_id'] = 'false';
        }
        $actions[] = array('label' => '获取退货结果',
                            'submit' => 'index.php?app=wmsvirtual&ctl=reship&act=batch_sync', 
                           'target' => 'refresh');
        $params = array(
            'use_buildin_recycle'=>false,
            'base_filter' => $base_filter,
            'actions'=>$actions,
            'title'=>'退货单',
        );
        
        $this->finder('ome_mdl_reship', $params);
    }

    /**
     * 发送至第三方
     * @
     * @
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batch_sync()
    {
        $this->begin('');
        kernel::database()->exec('commit');
        $oReship = app::get('ome')->model('reship');
        $ids = $_POST['reship_id'];
        if (!empty($ids)) {
            foreach ($ids as  $id) {
                $reship = $oReship->dump($id, 'branch_id,out_iso_bn,reship_bn');
                $branch_id = $reship['branch_id'];
                $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id );
                $data = array(
                    'out_order_code' =>$reship['out_iso_bn'],
                    'stockout_bn'=>$reship['reship_bn'],
                );
                //$result = kernel::single('middleware_wms_request', $wms_id)->reship_search($data, true);
                $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->reship_search($data);
                print_r($result);
                if ($result['rsp'] == 'success') {
                    $node_id = app::get('channel')->model('channel')->get_node_idBywms($wms_id);
                    kernel::single('wmsvirtual_reship')->result($result['data'],$node_id);
                }
                
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }
}
