<?php
class wmsvirtual_ctl_delivery extends desktop_controller{

    public function __construct()
    {
        $this->_rpc = kernel::single('wmsvirtual_rpc');
    }
    
    function index()
    {
        $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('ready','progress'),
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

        $actions[] = array('label' => '获取发货结果',
                            'submit' => 'index.php?app=wmsvirtual&ctl=delivery&act=batch_sync', 
                           
                            'target' => 'refresh');
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'actions'=>$actions,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'base_filter' => $base_filter,
            'title' => '待发货订单',
        );
        $this->finder('ome_mdl_delivery', $params);
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
        $ids = $_POST['delivery_id'];
        $oDelivery = app::get('ome')->model('delivery');
        if (!empty($ids)) {
            foreach ($ids as  $deliveryid) {
                $delivery = $oDelivery->dump($deliveryid,'delivery_bn,branch_id');
                $wms_id = kernel::single('ome_branch')->getWmsIdById($delivery['branch_id']);
                $result = kernel::single('ome_event_trigger_delivery')->search($wms_id, $delivery, true);
                $result = array(
                    'rsp'=>'success',
                    'data'=>array(
                        'delivery_bn'=>'1405261100005',
                        'logi_no'=>'3444444',
                        'status'=>'DELIVERY',
                        'logistics'=>'STO',
                        'operate_time'=>date('Y-m-d H:i:s'),
                ),
                );
                if ($result['rsp'] == 'success') {
                    $node_id = app::get('channel')->model('channel')->get_node_idBywms($wms_id);
                    kernel::single('wmsvirtual_delivery')->result($result['data'],$node_id);
                }
                
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }

    
   
}
