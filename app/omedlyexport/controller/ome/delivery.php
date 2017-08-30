<?php
class omedlyexport_ctl_ome_delivery extends desktop_controller{
    function index(){
        $title = '';
        if(isset($_POST['delivery_bn']) && $_POST['delivery_bn']){
            $deliveryObj = &app::get('ome')->model('delivery');
            $rows = $deliveryObj->getParentIdBybn($_POST['delivery_bn']);
            if($rows){
                foreach($rows as $val){
                    $deliveryId[] = $val['parent_id'];
                }
                $filter['extend_delivery_id'] = $deliveryId;
            }
        }
        $filter['type'] = 'normal';
        $status_cfg = &app::get('ome')->getConf('ome.delivery.status.cfg');
        if (isset($_GET['sync']) && $_GET['sync']) {
            $filter['sync'] = $_GET['sync'];
        }
        //分析status的filter条件
        $tmp_filter = $this->analyseStatus($_GET['status']);
        $filter = array_merge($filter,$tmp_filter);

        /*
         * 获取操作员管辖仓库
         */
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_ids = $oBranch->getBranchByUser(true);
           if ($branch_ids){
                $filter['branch_id'] = $branch_ids;
           }else{
                $filter['branch_id'] = 'false';
           }
        }
        $attach = '&status='.$_POST['status'].'&logi_id='.$_POST['logi_id'];
        $params = array(
                        'title'=>$filter['_title_'],
                        'actions' => array(
                                'stock' => array(
                                    'label' => '打印备货单',
                                    'submit' => 'index.php?app=ome&ctl=admin_receipts_print&act=toPrintStock'.$attach,
                                    'target' => "_blank",
                                ),
                                'delie' => array(
                                    'label' => '打印发货单',
                                    'submit' => 'index.php?app=ome&ctl=admin_receipts_print&act=toPrintMerge'.$attach,
                                    'target' => '_blank',
                                ),
                                'expre' => array(
                                    'label' => '打印快递单',
                                    'submit' => 'index.php?app=ome&ctl=admin_receipts_print&act=toPrintShip'.$attach,
                                    'target' => '_blank',//"dialog::{width:800,height:600,title:'设置标签'}",//
                                ),
                        ),
                        'base_filter' => $filter,
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                        'max_actions'=>8,
                        'use_view_tab'=>true,
                        //从载方法 以解决 发货中未录入快递单号不能过滤的bug

                    );

        

        $this->finder('omedlyexport_mdl_ome_delivery', $params);
    }

    function analyseStatus($status, $type='normal'){
        if ($type == 'normal'){
            switch ($status){
                case '':
                    $title = '全部';
                    $filter = array();
                    $filter['pause'] = "FALSE";
                    $filter['status'] = array('ready','progress','succ');
                    break;
                case 'progress':
                    $filter['status'] = array('ready','progress');;
                    break;
                case 'succ':
                    $title = '已发货';
                    $filter['process'] = "TRUE";
                    $filter['pause'] = "FALSE";
                    $filter['status'] = array('succ');
                    break;
                case 'stop':
                    $title = '暂停列表';
                    $filter['pause'] = "TRUE";
                    break;
            }
        }elseif ($type == 'refunded'){
            switch ($status){
                case '':
                    $title = '未发货';
                    $filter['process'] = "FALSE";
                    $filter['pause'] = "FALSE";
                    break;

                case 'succ':
                    $title = '已发货';
                    $filter['process'] = "TRUE";
                    $filter['pause'] = "FALSE";
                    break;
                case 'stop':
                    $title = '暂停列表';
                    $filter['pause'] = "TRUE";
                    break;
            }
        }
        //默认条件
        $filter['parent_id'] = 0;
        $filter['disabled'] = 'false';
        //$filter['status'] = array('ready','progress','succ');
        $filter['_title_'] = $title;
        $schema = app::get('ome')->model('delivery')->schema;
        if(isset($_POST['status']) && $schema['columns']['status']['type'][$_POST['status']]){
            $filter['status'] = $_POST['status'];
        }

        return $filter;
    }
    
 
}