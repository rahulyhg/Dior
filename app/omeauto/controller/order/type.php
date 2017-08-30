<?php

/**
 * 设置订单类型
 * 
 * @author hzjsq
 * @version 0.1
 */
class omeauto_ctl_order_type extends omeauto_controller {
    var $workground = "setting_tools";
    var $base_filter = array('group_type'=>'order');

    function _views() {
        $typeObj = $this->app->model('order_type');
        $sub_menu = array(
            0 => array('label' => '全部', 'filter' => $this->base_filter, 'optional' => false),
            1 => array('label' => '审单用到', 'filter' => array('oid|bthan' => 1), 'optional' => false),
            2 => array('label' => '分派用到', 'filter' => array('did|bthan' => 1), 'optional' => false),
            3 => array('label' => '仓库分配用到', 'filter' => array('bid|bthan' => 1), 'optional' => false),
        );
        $i = 0;
        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $typeObj->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=omeauto&ctl=' . $_GET['ctl'] . '&act=' . $_GET['act'] . '&view=' . $i++;
        }
        return $sub_menu;
    }

    function index() {
        $params = array(
            'title' => '分组规则设置',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => false,
            'use_view_tab' => true,
            'finder_aliasname' => 'order_type_view_v1',
            'finder_cols' => 'column_confirm,column_disabled,name,column_autoconfirm,column_autodispatch,column_autobranch,column_memo,column_order,column_content',
            'base_filter' => $this->base_filter,
        );

        $params['actions'] = array(
            array('label' => '新建', 'href' => 'index.php?app=omeauto&ctl=order_type&act=add', 'target' => 'dialog::{width:760,height:480,title:\'新建分组规则\'}'),
        );

        $this->finder('omeauto_mdl_order_type', $params);
    }

    function delivery() {
        $params = array(
            'title' => '发货单分组规则',
            'use_buildin_setcol' => false,
            'use_buildin_recycle' => false,
            'use_view_tab' => false,
            'finder_aliasname' => 'order_type_delivery',
            'finder_cols' => 'name,delivery_group,column_memo,column_content',
            'base_filter' => $this->base_filter,
        );

        $params['actions'] = array(
            array('label' => '应用到发货单分组', 'submit' => 'index.php?app=omeauto&ctl=order_type&act=toDelivery&status=true'),
            array('label' => '取消发货单分组', 'submit' => 'index.php?app=omeauto&ctl=order_type&act=toDelivery&status=false'),
        );

        $this->finder('omeauto_mdl_order_type', $params);
    }

    function toDelivery(){
        $this->begin('index.php?app=omeauto&ctl=order_type&act=delivery');
        if($_GET['status'] && $_GET['status']=='true'){
            $data['delivery_group'] = 'true';
        }else{
            $data['delivery_group'] = 'false';
        }

        if($_POST['isSelectedAll'] && $_POST['isSelectedAll'] == '_ALL_'){
            $filter = array();
        }elseif($_POST['tid'] && is_array($_POST['tid'])){
            $filter = array('tid'=>$_POST['tid']);
        }else{
            $this->end(false, app::get('omeauto')->_('操作失败。'));
        }

        $orderTypeObj = app::get('omeauto')->model('order_type');
        $orderTypeObj->update($data,$filter);
        $this->end(true, app::get('omeauto')->_('操作成功。'));
    }

    function add() {
        $group_type = isset($_GET['group_type']) ? $_GET['group_type'] : 'order';
        $this->pagedata['group_type'] = $group_type;
        $this->page('order/type/add.html');
    }
    
    function edit($tid) {
        
        $info = app::get('omeauto')->model('order_type')->dump(intval($tid));
        if (!empty($info)) {
            foreach ($info['config'] as $key => $row) {
                $info['config'][$key] = array('json' => $row, 'attr' => json_decode($row, true));
            }
            $this->pagedata['info'] = $info;
        } else {
            $this->pagedata['info'] = array();
        }
        $this->page('order/type/add.html');
    }
    
    function addrole() {
        if (!empty($_REQUEST['role'])) {
            $role = json_decode(stripcslashes($_REQUEST['role']), true);
        } else {
            $role = array();
        }

        $this->pagedata['uid'] = $_REQUEST['uid'];
        $this->pagedata['role'] = base64_encode(stripcslashes($_REQUEST['role']));
        $this->pagedata['init'] = $role;
        $this->page('order/type/addrole.html');
    }
    
    function setStatus($oid, $status) {
        
        if ($status == 'true') {
            $disabled = 'false';
        } else {
            $disabled = 'true';
        }
        kernel::database()->query("update sdb_omeauto_order_type set disabled='{$disabled}' where tid={$oid}");

        echo "<script>parent.MessageBox.success('命令已经被成功发送！！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }
    
    function ajax() {
        $params = $_POST['p'];
        $typeName = array_shift($params);
        $method = array_shift($params);
        
        echo kernel::single('omeauto_auto_type')->doMethod($typeName, $method, $params);
    }
    
    function createRole() {
        
        echo kernel::single('omeauto_auto_type')->createRole();
    }
    
    function save() {
        
        $sdf['name']= $_POST['name'];
        $sdf['memo']= $_POST['memo'];
        $sdf['weight']= $_POST['weight']?$_POST['weight']:0;
        $sdf['config'] = explode('|||', $_POST['roles']);
        $sdf['group_type'] = $_POST['group_type'] ? $_POST['group_type']:'order';
        
        $tid = intval($_REQUEST['tid']) ;
        if (!empty($tid) && $tid>0) {
            $sdf['tid'] = $tid;
        }
        app::get('omeauto')->model('order_type')->save($sdf);
        echo "SUCC";
    }

    
    
}