<?php
class purchase_roles
{
    var $purchase_roles = array(
        'ome_ctl_admin_invoice' =>array(
            'name'=>'采购单据',
            'acts'=>array(
                array(
                    'name'=>'采购付款单',
                    'act'=>'purchase:admin_purchase_payments:index',
                    'url'=>'index.php?app=purchase&ctl=admin_purchase_payments&p[0]=2'
                ),
                array(
                    'name'=>'采购赊购单',
                    'act'=>'purchase:admin_credit_sheet:index',
                    'url'=>'index.php?app=purchase&ctl=admin_credit_sheet&p[0]=2'
                ),
                array(
                    'name'=>'采购退款单',
                    'act'=>'purchase:admin_purchase_refunds:index',
                    'url'=>'index.php?app=purchase&ctl=admin_purchase_refunds&p[0]=2'
                ),
                array(
                    'name'=>'采购单据作废',
                    'act'=>'purchase:admin_purchase_payments:dend_payments',
                    'url'=>'index.php?app=purchase&ctl=admin_purchase_payments&p[0]=3'
                ),
                array(
                    'name'=>'采购结算统计表',
                    'act'=>'purchase:admin_statement:ClearingTables',
                    'url'=>'index.php?app=purchase&ctl=admin_statement&act=ClearingTables'
                ),
                array(
                    'name'=>'盘点损益汇总表',
                    'act'=>'purchase:admin_inventory:counterTables',
                    'url'=>'index.php?app=purchase&ctl=admin_inventory&act=counterTables'
                ),
            ),
        ),
        'ome_ctl_admin_finance' =>array(
            'name'=>'采购财务',
            'acts'=>array(
                array(
                    'name'=>'采购付款单',
                    'act'=>'purchase:admin_purchase_payments:index',
                    'url'=>'index.php?app=purchase&ctl=admin_purchase_payments&p[0]=1'
                ),
                array(
                    'name'=>'采购赊购单',
                    'act'=>'purchase:admin_credit_sheet:index',
                    'url'=>'index.php?app=purchase&ctl=admin_credit_sheet&p[0]=1'
                ),
                array(
                    'name'=>'采购退款单',
                    'act'=>'purchase:admin_purchase_refunds:index',
                    'url'=>'index.php?app=purchase&ctl=admin_purchase_refunds&p[0]=1'
                ),
                array(
                    'name'=>'盘点表损益确认',
                    'act'=>'purchase:admin_inventory:confirm',
                    'url'=>'index.php?app=purchase&ctl=admin_inventory&act=confirm&p[0]=1'
                ),
            ),
        ),
        'ome_ctl_admin_storage' =>array(
            'name'=>'盘点表',
            'acts'=>array(
                array(
                    'name'=>'盘点导出',
                    'act'=>'purchase:admin_inventory:export',
                    'url'=>'index.php?app=purchase&ctl=admin_inventory&act=export'
                ),
                array(
                    'name'=>'盘点导入',
                    'act'=>'purchase:admin_inventory:import',
                    'url'=>'index.php?app=purchase&ctl=admin_inventory&act=import'
                ),
                array(
                    'name'=>'调拨单查看',
                    'act'=>'purchase:admin_appropriation:index',
                    'url'=>'index.php?app=purchase&ctl=admin_appropriation'
                ),
            ),
        ),
        'purchase_ctl_admin_purchase' =>array(
            'name'=>'采购管理',
            'acts'=>array(
                array(
                    'name'=>'采购订单',
                    'act'=>'purchase:admin_purchase:index',
                    'url'=>'index.php?app=purchase&ctl=admin_purchase'
                ),
                array(
                    'name'=>'待入库',
                    'act'=>'purchase:admin_purchase:eoList',
                    'url'=>'index.php?app=purchase&ctl=admin_purchase&act=eoList'
                ),
                array(
                    'name'=>'入库单',
                    'act'=>'purchase:admin_eo:index',
                    'url'=>'index.php?app=purchase&ctl=admin_eo'
                ),
                array(
                    'name'=>'采购退货单',
                    'act'=>'purchase:admin_returned_purchase:index',
                    'url'=>'index.php?app=purchase&ctl=admin_returned_purchase&p[0]=eo'
                ),
                array(
                    'name'=>'供应商管理',
                    'act'=>'purchase:admin_supplier:index',
                    'url'=>'index.php?app=purchase&ctl=admin_supplier'
                ),
            ),
        ),
    );


    function get_role_by_userid($userid)
    {
        $app = app::get('ome');
        $mdl = $app->model('role_acts');
        $roles = $mdl->getList("*",array("user_id"=>$userid));
        $acts = array();
        $app_acts = array();
        if ($roles)
        foreach ($roles as $k=>$onerole)
        {
            $acts[] = $onerole['act'];
            $roles[$k]['display_name'] = $this->purchase_roles[$onerole['role_id']]['name'];
            if ($this->purchase_roles[$onerole['role_id']]['acts'])
            foreach ($this->purchase_roles[$onerole['role_id']]['acts'] as $getname)
            {
                if ($getname['act'] == $onerole['act'])
                {
                    $roles[$k]['name'] = $getname['name'];
                    $roles[$k]['url'] = $getname['url'];
                    break;
                }
            }
        }
        foreach ($this->purchase_roles as $workground=>$oneworkground)
        {
            $wg_acts = $oneworkground['acts'];
            if (is_array($wg_acts))
            {

                foreach ($wg_acts as $oneact)
                {
                $app_acts[] = $oneact['act'];
                    if (!in_array($oneact['act'],$acts))
                    {
                        $addrole = array();
                        $addrole['user_id'] = $userid;
                        $addrole['role_id'] = $workground;
                        $addrole['act'] = $oneact['act'];
                        $addrole['name'] = $oneact['name'];
                        $addrole['display_name'] = $oneworkground['name'];
                        $addrole['disabled'] = 'true';
                        $roles[] = $addrole;
                    }
                }
            }
        }
//        //如果没有workground的权限，则unset掉此workground
        //get roles from app-desktop
        $desktopapp = app::get('desktop');
        $baseapp = app::get('base');
        $curr_user_info = $desktopapp->model('users')->dump($userid,'*',array('roles'=>array('*')));
        $curr_userrole = $curr_user_info['roles'];
        $workground_roleids = array();
        $workground_roles = array();
        if (is_array($curr_userrole))
        {
            foreach ($curr_userrole as $managegroupid)
            {
                $wg_roles = $desktopapp->model('roles')->dump($managegroupid['role_id'],'workground');
                $workground_roleids = array_merge($workground_roleids,unserialize($wg_roles['workground']));
            }
            foreach($workground_roleids as $wgid)
            {
              $roleinfo = $baseapp->model('app_content')->dump(array("content_id"=>$wgid));
              $workground_roles[] = $roleinfo['content_name'];
            }
        }

        foreach ($roles as $k=>$v)
        {
            if (!in_array($v['role_id'],$workground_roles))
            {
                unset($roles[$k]);
            }
            if (!in_array($v['act'],$app_acts))
            {
                unset($roles[$k]);
            }
        }
        //echo "<pre>";
        //print_r($roles);
        return $roles;
    }
}