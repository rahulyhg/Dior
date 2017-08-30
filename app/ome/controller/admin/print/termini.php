<?php
class ome_ctl_admin_print_termini extends desktop_controller{
    var $name = "大头笔设置";
    var $workground = "setting_tools";

    function index(){
        $actions = array(
            array(
                'label' => '添加大头笔',
                'href' => 'index.php?app=ome&ctl=admin_print_termini&act=add&finder_id='.$_GET['finder_id'],
                'target' => "dialog::{width:600,height:430,title:'添加大头笔'}",
            ),
        );
        $params = array(
            'title'=>'大头笔设置',
            'actions'=>$actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
        );
        $this->finder('ome_mdl_print_tag', $params);
    }

    function add() {
        $this->_edit();
    }

    function edit($tag_id) {
        $this->_edit($tag_id);
    }

    private function _edit($tag_id=NULL) {
        $data = array();
        if(!empty($tag_id) && $tag_id>0){
            $printTagObj = app::get('ome')->model('print_tag');
            $data = $printTagObj->dump($tag_id);
            $data['config']= unserialize($data['config']);
        }
        $this->pagedata['data'] = $data;
        $this->page('admin/print/express_termini.html');
    }

    function save() {
        $data['name']= $_POST['name'];
        $data['intro']= $_POST['intro'];
        $data['config']= serialize($_POST['config']);
        $data['create_time']= time();
        $tag_id = intval($_POST['tag_id']) ;
        if (!empty($tag_id) && $tag_id>0) {
            $data['tag_id'] = $tag_id;
            unset($data['create_time']);
        }
        app::get('ome')->model('print_tag')->save($data);
        echo "SUCC";
    }
}
?>
