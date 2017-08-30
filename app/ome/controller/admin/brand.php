<?php
class ome_ctl_admin_brand extends desktop_controller{

    var $workground = 'goods_manager';

    function index(){
        $this->finder('ome_mdl_brand',array(
            'title'=>'商品品牌',
            'actions' => array(
                 array('label'=>'添加','href'=>'index.php?app=ome&ctl=admin_brand&act=create','target'=>'_blank'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>true,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>true,
             'orderBy' =>'ordernum DESC'
            ));
    }

    function getCheckboxList(){
        $brand = &$this->app->model('brand');
        $this->pagedata['checkboxList'] = $brand->getList('brand_id,brand_name',null,0,-1);
        $this->display('admin/goods/brand/checkbox_list.html');
    }

    function create(){
		$this->pagedata['title'] = '添加商品品牌';
        $this->singlepage('admin/goods/brand/detail.html');
    }

    function save(){
        $this->begin('index.php?app=ome&ctl=admin_brand&act=index');
        $objBrand = &$this->app->model('brand');
        $brand_name = addslashes($_POST['brand_name']);
        
        if($_POST['brand_id']==''){
            $brand = $objBrand->dump(array('brand_name'=>$brand_name),'*');
            if(!empty($brand)){
                $this->end(false,app::get('base')->_('品牌已存在!不可以继续添加'));
            }
        }
        foreach($_POST as $k=>$v){
            $v['brand_name']=addslashes($v['brand_name']);
        }
       $this->end($objBrand->save($_POST),app::get('base')->_('品牌保存成功'));
        
    }

}
