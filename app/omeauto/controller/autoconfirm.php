<?php

class omeauto_ctl_autoconfirm extends omeauto_controller {

    var $workground = "setting_tools";
    
    function index() {
        $params = array(
            'title' => '自动审单规则',
            'actions' => array(
                array(
                    'label' => '新建',
                    'href' => 'index.php?app=omeauto&ctl=autoconfirm&act=add',
                    'target' => 'dialog::{width:700,height:480,title:\'新建审单规则\'}',
                )
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
            'use_buildin_filter' => false,
            'use_view_tab' => false,
            'finder_cols' => 'column_confirm,column_disabled,name,column_order,column_content',
        );
        $this->finder('omeauto_mdl_autoconfirm', $params);
    }

    function add() {
        $this->_edit();
    }

    function edit($oid) {

        $this->pagedata['data'] = app::get('omeauto')->model('autoconfirm')->dump(array('oid' => $oid), '*');
        $this->_edit($oid);
    }

    private function _edit($oid=NULL) {

        $this->pagedata['orderType'] = $this->getOrderType();
        $this->page('autoconfirm/add.html');
    }

    function do_add() {

        //$this->begin("index.php?app=omeauto&ctl=autoconfirm&act=index");
        $data = $_POST;
        //修改
        if ($data['oid']) {
             kernel::database()->query("update sdb_omeauto_order_type set oid=0 where oid={$data[oid]}");
        }
        app::get('omeauto')->model('autoconfirm')->save($data);
        //更新订单类型相关表
        foreach( (array)$data['config']['autoOrders'] as $tid) {
            
            kernel::database()->query("update sdb_omeauto_order_type set oid={$data[oid]} where tid={$tid}");
            //$sdf = array('oid' => $data['oid']);
            //var_dump(app::get('omeauto')->model('order_type')->update($sdf, array('tid' => $tid)));
        }
        //$this->end(true, '保存成功');
        
        echo "SUCC";
    }
    
    function setStatus($oid, $status) {
        
        if ($status == 'true') {
            $disabled = 'false';
        } else {
            $disabled = 'true';
        }
        kernel::database()->query("update sdb_omeauto_autoconfirm set disabled='{$disabled}' where oid={$oid}");
        
        echo "<script>parent.MessageBox.success('命令已经被成功发送！！');parent.finderGroup['{$_GET[finder_id]}'].refresh();</script>";
        exit;
    }
    
    function setDefaulted($oid) {
        
        if ($oid && $oid > 0) {
            $confirmObj = app::get('omeauto')->model('autoconfirm');
            $data = $confirmObj->dump($oid, 'oid,config');
            unset($data['config']['autoOrders']);
            $upData = array(
                'defaulted'=>'true',
                'config'=>$data['config'],
            );
            //全部取消缺省
            kernel::database()->query("update sdb_omeauto_autoconfirm set defaulted='false'");
            //全部取消缺省
            kernel::database()->query("update sdb_omeauto_order_type set oid=0 where oid={$oid}");
            //置指定仓库为缺省发货仓库
            $confirmObj->update($upData,array('oid'=>$oid));
        }
        //$this->end(true, '默认发货仓设置成功！！');
        echo "<script>alert('默认审单规则设置成功！！');top.finderGroup['{$_REQUEST[finder_id]}'].refresh();</script>";
    }
    
    function removeDefaulted($oid) {
        
        if ($oid && $oid > 0) {
            //置指定仓库为缺省发货仓库
            kernel::database()->query("update sdb_omeauto_autoconfirm set defaulted='false' where oid={$oid}");
        }
        echo "<script>alert('取消默认审单规则设置成功！！');top.finderGroup['{$_REQUEST[finder_id]}'].refresh();</script>";
    }

    private function getOrderType() {
        
        $info = app::get('omeauto')->model('order_type')->getList('*', array('disabled' => 'false','group_type'=>'order'), 0, -1);
        foreach ($info as $idx => $rows) {
            $title = '';
            foreach ($rows['config'] as $row) {

                $role = json_decode($row, true);
                $title .= $role['caption'] . "\n";
            }
            $info[$idx]['title'] = $title; 
        }
       
        return $info;
    }
}