<?php
class wmsvirtual_ctl_stockout extends desktop_controller{

    
   function index()
    {
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

        $actions[] = array('label' => '获取出库结果',
                            'submit' => 'index.php?app=wmsvirtual&ctl=stockout&act=batch_sync', 
                            'target' => 'refresh'
                         );

        $base_filter = array(
            
            //'iso_status' => array('1','2'),
        );

        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'actions'=>$actions,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'base_filter' => $base_filter,
            'title'=>'待出库',
        );
        
         $params['base_filter']['type_id'] = kernel::single('taoguaniostockorder_iostockorder')->get_create_iso_type(0,true);
        $this->finder('taoguaniostockorder_mdl_iso', $params);
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
        $ids = $_POST['iso_id'];
        $oIso = &app::get('taoguaniostockorder')->model("iso");
        if (!empty($ids)) {
            foreach ($ids as  $isoid) {
                $iso_data = $oIso->dump(array('iso_id'=>$isoid),'branch_id,out_iso_bn,iso_bn');
                $data = array(
                     'out_order_code'=>$iso_data['out_iso_bn'],
                    'stockout_bn'=>$iso_data['iso_bn'],
                );
                $wms_id = kernel::single('ome_branch')->getWmsIdById($iso_data['branch_id']);
                
                $result = kernel::single('console_event_trigger_otherstockout')->search($wms_id, $data, true);
                if ($result['rsp'] == 'success') {
                    $node_id = app::get('channel')->model('channel')->get_node_idBywms($wms_id);
                    kernel::single('wmsvirtual_stockout')->result($result['data'],$node_id);
                }
                
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }
    
   
}
