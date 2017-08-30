<?php
class ome_ctl_admin_specification extends desktop_controller{

    var $workground = 'goods_manager';

    function index(){
        $this->finder('ome_mdl_specification',array(
            'title'=>'商品规格',
            'actions'=>array(
                  array('label'=>'新建','href'=>'index.php?app=ome&ctl=admin_specification&act=add','target'=>'_blank'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
              'orderBy' =>'spec_id DESC'
        ));
    }

    function add(){
		$this->pagedata['title'] = '新建商品规格';
        $this->singlepage('admin/goods/specification/detail.html');
    }

    function save(){
        $this->begin('index.php?app=ome&ctl=admin_specification&act=index');
        $oSpec = &$this->app->model('specification');
        if(!$_POST['spec']['spec_value']){
            $this->end(false,'请输入规格值');
            exit;
        }
        $spec_value = array();
        foreach( $_POST['spec']['spec_value'] as $specValue ){

            if( $specValue['spec_value'] == '' ){
                $this->end(false,'规格值不能为空');
                exit;
            }
            $spec_value[] = $specValue['spec_value'];

        }
        $spec_value_count = array_count_values($spec_value);
        foreach($spec_value_count as $spec=>$value){
            if($value>1){
                $this->end(false,$spec.'：规格值重复');
            }
        }
        $this->end($oSpec->save($_POST['spec']),'操作成功');
    }

    function edit( $specId ){
        $oSpec = &$this->app->model('specification');
        $subsdf = array(
            'spec_value'=>array('*')
        );
        $this->pagedata['spec'] = $oSpec->dump($specId,'*',$subsdf);
        $this->singlepage('admin/goods/specification/detail.html');
    }

    function check_spec_value_id(){
        $oSpecIndex = &$this->app->model('goods_spec_index');
        if( !$oSpecIndex->dump($_POST) )
            echo "can";
        else
            echo app::get('base')->_("该规格值已绑定商品");
    }

    function selSpecDialog($typeId = 0) {
        $aSpec = array();
        if($typeId){
            //$aSpec = $objSpec->getListByTypeId($typeId);
        }else{
            $oSpec = &$this->app->model('specification');
            $aSpec = $oSpec->getList('spec_id,spec_name,spec_memo',null,0,-1);
        }
        $this->pagedata['specs'] = $aSpec;
        $this->display('admin/goods/specification/spec_select.html');
    }

    function previewSpec(){
        $oSpec = &$this->app->model('specification');
        $this->pagedata['spec'] = $oSpec->dump( $_POST['spec_id'], '*',array('spec_value'=>array('*')));
        $this->pagedata['spec_default_pic'] = $this->app->getConf('spec.default.pic');
        $this->display('admin/goods/specification/spec_value_preview.html');
    }

}
