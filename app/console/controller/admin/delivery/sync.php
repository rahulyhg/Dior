<?php
class console_ctl_admin_delivery_sync extends desktop_controller {

    var $name = "发货单撤销失败列表";
    var $workground = "console_center";


    /**
     *
     * 发货单列表
     */
    function index(){

        $user = kernel::single('desktop_user');
        
        $actions = array();
       
       $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'status' => array('ready','progress','succ'),
            'sync' => array('fail'),
        );
        $base_filter = array_merge($base_filter,$_GET);
        switch ($_GET['view']) {
            case '1':
                $actions[] =  array('label' => '重试取消发货单', 'submit' => 'index.php?app=console&ctl=admin_delivery_sync&act=batch_sync', 'confirm' => '你确定要对勾选的发货单进行发货取消吗？', 'target' => 'refresh');

                $actions[] = array('label' => '发货单取消', 'submit' => 'index.php?app=console&ctl=admin_delivery_sync&act=batch_cancel', 'confirm' => "这些发货单认为都是在仓储已经取消发货，请确认这些发货单WMS已经取消！！！\n\n警告：本操作将会直接取消发货单并释放库存，并不可恢复，请谨慎使用！！！", 'target' => 'refresh');
                break;
        }
        
       
        if ($_GET['view'] == '1') {
            $query_status = 'progress';
        }elseif($_GET['view'] == '2'){
            $query_status = 'succ';
        }
        $actions[] =  array(
            'label'=>'导出',
            'submit'=>'index.php?app=omedlyexport&ctl=ome_delivery&act=index&action=export&status='.$query_status.'&sync=fail',
            'target'=>'dialog::{width:400,height:170,title:\'导出\'}'
        ); 
        $params = array(
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_import'=>false,
            'use_buildin_export'=>false,
            'use_buildin_filter'=>true,
            'use_view_tab'=>true,
            'actions' => $actions,
            'title'=>'发货单撤销失败列表',
            'base_filter' => $base_filter,
        );

        
        $this->finder('console_mdl_delivery', $params);
    }

    //未发货 已发货 全部
    function _views(){
        $oDelivery = app::get('console')->model('delivery');
        $base_filter = array(
            'type' => 'normal',
            'pause' => 'false',
            'parent_id' => 0,
            'disabled' => 'false',
            'sync' => array('fail'),

        );
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'filter'=>array('status' => array('ready','progress','succ')),'optional'=>false),
            1 => array('label'=>app::get('base')->_('待发货'),'filter'=>array('process'=>array('FALSE'),'status'=>array('progress','ready')),'optional'=>false),
            2 => array('label'=>app::get('base')->_('已发货'),'filter'=>array('process'=>array('TRUE'),'status'=>'succ'),'optional'=>false),
           
        );
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $oDelivery->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=console&ctl='.$_GET['ctl'].'&act='.$_GET['act'].'&view='.$i++;
        }

        return $sub_menu;
    }

    
    /**
     * 批量同步.
     * @param 
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batch_sync()
    {
        $this->begin('');
        kernel::database()->exec('commit');
        $ids = $_REQUEST['delivery_id'];
        $branchLib = kernel::single('ome_branch');
        $eventLib = kernel::single('ome_event_trigger_delivery');
        $oOperation_log = &app::get('ome')->model('operation_log');
        $deliveryObj = app::get('ome')->model('delivery');
        if (!empty($ids)) {

            $delivery_list = kernel::database()->select("SELECT delivery_id,branch_id,delivery_bn from sdb_ome_delivery where delivery_id in (" . join(',', $ids) . ") and sync in ('fail') AND status in('ready','progress')");
            foreach ((array) $delivery_list as $delivery) {

                $wms_id = $branchLib->getWmsIdById($delivery['branch_id']);
                $res = $eventLib->cancel($wms_id,array('outer_delivery_bn'=>$delivery['delivery_bn']),true);
                $delivery_id = $delivery['delivery_id'];
                if ($res['rsp'] == 'fail') {
                    $oOperation_log->write_log('delivery_back@ome',$delivery_id,'发货单取消失败,原因'.$res['msg']);
                    

                }else{
                    $this->update_sync_cancel($delivery_id,'succ');
                    $oOperation_log->write_log('delivery_back@ome',$delivery_id,'发货单取消成功');
                }
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }

    
    /**
     * 批量取消.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batch_cancel()
    {
        $this->begin('');
        kernel::database()->exec('commit');
        $ids = $_REQUEST['delivery_id'];
        $branchLib = kernel::single('ome_branch');
        $eventLib = kernel::single('ome_event_trigger_delivery');
        $oOperation_log = &app::get('ome')->model('operation_log');
        $deliveryObj = app::get('ome')->model('delivery');
        if (!empty($ids)) {

            $delivery_list = kernel::database()->select("SELECT delivery_id,branch_id,delivery_bn from sdb_ome_delivery where delivery_id in (" . join(',', $ids) . ") and sync in ('fail') AND status in('ready','progress')");
            
            foreach ((array) $delivery_list as $delivery) {
                $data = array(
                    'status'=>'cancel',
                    'memo'=>'发货单请求第三方仓储取消失败,强制取消!',
                    'delivery_bn'=>$delivery['delivery_bn'],
                );
                kernel::single('ome_event_receive_delivery')->update($data);
            }
        }
        $this->end(true, '命令已经被成功发送！！');
    }
   
}
