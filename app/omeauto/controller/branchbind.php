<?php
class omeauto_ctl_branchbind extends desktop_controller {
    var $workground = "goods_manager";

    function index() {
        $params = array(
            'title' => '仓库绑定设置',
            'base_filter' => array('disabled' => 'false','is_deliv_branch' => 'true'),
            'use_buildin_recycle' => false,
            'finder_cols' => 'column_edit,name,branch_bn,is_deliv_branch,weight,memo',
        );
        $this->finder('omeauto_mdl_branch', $params);
    }

    /**
     * 设置“线上发货仓库”绑定“线上备货仓库”
     * 
     * @param integer $branch_id 仓库ID
     * @return void
     */
    function setBind($branch_id) {
        $branchObj = app::get('ome')->model('branch');
        $branchList = $branchObj->getList('*',array('disabled' => 'false','is_deliv_branch' => 'false'));
        $branch = $branchObj->dump($branch_id);
        $branch['bind_conf'] = unserialize($branch['bind_conf']);

        $this->pagedata['branch'] = $branch;
        $this->pagedata['branchList'] = $branchList;
        $this->page('branchbind/setBind.html');
    }

    /**
     * 保存仓库绑定信息
     * 
     * @return void 
     */
    function save() {
        //$this->begin("index.php?app=omeauto&ctl=autoconfirm&act=index");
        $data = $_POST;
        $data['bind_conf'] = $data['bind_conf'] ? serialize($data['bind_conf']) : '';
        //修改
        if ($data['branch_id']) {
            $branchObj = app::get('ome')->model('branch');
            $branchObj->update(array('bind_conf'=>$data['bind_conf']),array('branch_id'=>$data['branch_id']));
        }
        echo "SUCC";
    }
}