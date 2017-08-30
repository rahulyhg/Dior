<?php
class wmsvirtual_ctl_purchasereturn extends desktop_controller{

    function index()
    {
        

        $base_filter = array(
            'rp_type' => 'eo',
            'return_status'=>'1',
        );
        $actions = array();
        $branchObj = &app::get('ome')->model('branch');
        $node_type = array('jd_wms','ilc');
        $wms_id = &app::get('channel')->model('channel')->get_wmd_idBynodetype($node_type);
        $branchs = $branchObj->getList('branch_id',array('wms_id'=>$wms_id),0,-1);
        
         $branch_ids = array();
         foreach ($branchs as $branch ) {
             $branch_ids[] = $branch['branch_id'];
         }
         if ($branch_ids){
            $base_filter['branch_id'] = $branch_ids;
        }else{
            $base_filter['branch_id'] = 'false';
        }
        $actions[] = array('label' => '获取采购退货结果',
                            'submit' => 'index.php?app=wmsvirtual&ctl=purchasereturn&act=batch_sync', 
                           
                            'target' => 'refresh');
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'actions'=>$actions,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'base_filter' => $base_filter,
            'title'=>'待出库采购退货单',
        );
        $this->finder('purchase_mdl_returned_purchase', $params);
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
        $Opo = app::get('purchase')->model('returned_purchase');
        $ids = $_POST['rp_id'];
        if (!empty($ids)) {
            foreach ($ids as  $id) {
                $po = $Opo->dump($id, 'branch_id,out_iso_bn,rp_bn');
                $branch_id = $po['branch_id'];
                $wms_id = kernel::single('ome_branch')->getWmsIdById($branch_id );
                $data = array(
                    'out_order_code' =>$po['out_iso_bn'],
                    'stockout_bn'=>$po['rp_bn'],
                );
                //$result = kernel::single('middleware_wms_request', $wms_id)->stockout_search($data, true);
                $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->stockout_search($data);
                if ($result['rsp'] == 'success') {
                    $node_id = app::get('channel')->model('channel')->get_node_idBywms($wms_id);
                    kernel::single('wmsvirtual_purchasereturn')->result($result['data'],$node_id);
                }
                
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }


}
