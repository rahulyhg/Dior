<?php
class wms_ctl_admin_refunded extends desktop_controller{

    var $name = "发货中心";
    var $workground = "wms_delivery";


    function index(){
        $op_id = kernel::single('desktop_user')->get_id();
        $title = '';
        $filter['type'] = 'reject';
        $filter['disabled'] = 'false';
        //$filter['status'] = array('ready','progress','succ');
        switch ($_GET['status']){
            case '':
                $title = '未发货';
                $filter['status'] = 0;
                break;
            case 1:
                $title = '未发货';
                $filter['status'] = 0;
                break;
            case 2:
                $title = '已发货';
                $filter['status'] = 3;
                break;
        }
        //如果不是超级管理员，则只能查看指定仓库相关的
        if (!$this->user->is_super())
        {
            //取得本管理员的组
            $Obranches = &app::get('ome')->model('branch');
            $oGroups = &app::get('ome')->model("groups");
            $oGroup_ops = &app::get('ome')->model("group_ops");
            $obranch_groups = &app::get('ome')->model("branch_groups");
            $user_groups = $oGroup_ops->getList('*',array('op_id'=>$this->user->get_id()));
            $user_branches = array();
            if ($user_groups)
            foreach($user_groups as $groups)
            {
                $groupid = $groups['group_id'];
                $branch_group = $obranch_groups->getList('*',array('group_id'=>$groupid));
                if($branch_group)
                foreach($branch_group as $branch)
                {
                    $user_branches[] = $branch['branch_id'];
                }
            }
            $user_branches = array_unique($user_branches);
            //取得本管理员的仓库
            if (!empty($user_branches))
            {
                $filter['branch_id'] = $user_branches;

            }
            //filter
        }

        $params = array(
            'title'=>$title,
            'actions' => array(
                    array(
                        'label' => '打印备货单',
                        'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintStock&from=refunded&status='.$_GET['status'],
                        'target' => "_blank",
                    ),
                    array(
                        'label' => '打印发货单',
                        'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintMerge&from=refunded&status='.$_GET['status'],
                        'target' => '_blank',
                    ),
                    array(
                        'label' => '打印快递单',
                        'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toPrintShip&from=refunded&status='.$_GET['status'],
                        'target' => '_blank',//"dialog::{width:800,height:600,title:'设置标签'}",//
                    ),
                    array(
                        'label' => '联合打印',
                        'submit' => 'index.php?app=wms&ctl=admin_receipts_print&act=toMergePrint&from=refunded&status='.$_GET['status'],
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
            'finder_aliasname' => 'delivery_refunded'.$op_id,
        );
        $this->finder('wms_mdl_delivery', $params);
    }
}
?>