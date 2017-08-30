<?php
class omeauto_ctl_autobind extends omeauto_controller{
    var $workground = "setting_tools";

    function index(){
        $params = array(
            'title'=>'发货单自动合并规则',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>false,
            'use_view_tab'=>false,
       );

       if(!$this->app->model('autobind')->getList("*",array(),0,1)){
           $params['actions'] = array(
                                    array(
                                        'label'=>'新建',
                                        'href'=>'index.php?app=omeauto&ctl=autobind&act=add',
                                        'target'=>'_blank',
                                        'id' => 'newautobind',
                                    ),
                                );
       }

       $this->finder('omeauto_mdl_autobind',$params);
    }



    function add(){
        $this->_edit();
    }

    function edit($oid){
        $this->_edit($oid);
    }

    private function _edit($oid=NULL){
        $conditions = kernel::single('omeauto_autobind')->get_conditions();

        if($oid){
            $oBind = &$this->app->model("autobind");
            $autobind = $oBind->dump($oid);
            $this->pagedata['autobind'] = $autobind;

            if($autobind['config']){
                $condition_detail = "";
                $i = 0;
                foreach($autobind['config'] as $condition=>$detail){
                    foreach($detail as $k=>$v){
                        $condition_detail[$i]['title'] = kernel::single($condition)->name;
                        $condition_detail[$i]['content'] = kernel::single('omeauto_auto')->get_condition_detail($condition,$v);
                        $condition_detail[$i]['condition'] = $condition;
                        $i++;

                        if(isset($conditions[$condition])){
                            $conditions[$condition]['disabled'] = true;
                        }
                    }
                }
                $this->pagedata['condition_detail'] = $condition_detail;
            }
        }

        $this->pagedata['conditions'] = $conditions;
		$this->pagedata['title'] = '订单自动分派规则添加/编辑';
        $this->singlepage('autobind/index.html');
    }

    function do_add(){
        $this->begin("index.php?app=omeauto&ctl=autobind&act=index");
        $data = $_POST;
        $oAutobind = &$this->app->model('autobind');
        $oAutobind->save($data);
        $this->end(true,'保存成功');
    }
}
