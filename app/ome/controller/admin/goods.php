<?php
class ome_ctl_admin_goods extends desktop_controller{
    var $workground = 'goods_manager';
    public $use_buildin_import = true;

    function index($supplier_id=null){
        if ($supplier_id){
            //根据供应商，获取商品列表
            $oSupplierGoods = app::get('purchase')->model('supplier_goods');
            $base_filter = $oSupplierGoods->getSupplierGoods($supplier_id);
        }
        
        $cat_id = intval($_GET['cat_id']);
        if ($cat_id){
            //按分类显示商品
            $base_filter = array('cat_id'=>$cat_id);
        }
        
        if ($_GET['action']!='to_export') {
            if (!isset($_POST['visibility'])) {
                $base_filter['visibility'] = 'true';
            }elseif(empty($_POST['visibility'])){
                unset($_POST['visibility']);
            }
        } else {
            // 导出
            if (isset($_GET['acti'])) {
                $_POST['acti'] = $_GET['acti'];
            }
        }
        $is_export = kernel::single('desktop_user')->has_permission('goods_export');#增加商品导出权限
        $is_export_cost = kernel::single('desktop_user')->has_permission('goods_export_cost');#增加导出成本价&重量模板权限
        $is_import_cost = kernel::single('desktop_user')->has_permission('goods_import_cost');#增加导入成本价&重量权限

        
        $params = array(
            'title'=>'商品',
            'actions'=>array(
                array('label' => '启用唯一码', 'submit' => 'index.php?app=ome&ctl=admin_goods&act=toSerial&status=true','target'=>'refresh'),
                array('label' => '取消唯一码', 'submit' => 'index.php?app=ome&ctl=admin_goods&act=toSerial&status=false','target'=>'refresh'),
                array('label' => '批量隐藏','submit' => 'index.php?app=ome&ctl=admin_goods&act=batchHide&p[0]=true','target'=>'refresh'),
                array('label' => '批量显示','submit' => 'index.php?app=ome&ctl=admin_goods&act=batchHide&p[0]=false','target'=>'refresh'),      
                'export_cost'=>array('label' => '导出成本价&重量模板','class'=>'export','icon'=>'add.gif','submit' => 'index.php?app=ome&ctl=admin_goods&act=index&action=export&acti=cost','target' => 'dialog::{width:400,height:170,title:\'导出\'}'),        
                'import_cost'=>array('label' => '批量导入成本价&重量','href' => 'index.php?app=ome&ctl=admin_goods&act=cost_import&acti=cost','target' => 'dialog::{width:400,height:150,title:\'批量导入成本价\'}'),
            ),
            'base_filter' => $base_filter,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_filter'=>true,
            'use_buildin_export'=>$is_export,
            'orderBy' =>'goods_id DESC'
        );
        if(!$is_export_cost){
            unset($params['actions']['export_cost']);
        }
         if(!$is_import_cost){
            unset($params['actions']['import_cost']);
        } 
         $this->finder('ome_mdl_goods',$params);
    }

    /**
     * 批量隐藏
     *
     * @return void
     * @author 
     * @param $type 是否隐藏
     **/
    public function batchHide($hide = false)
    {
        $_type = $hide;
        $goods_id = kernel::single('base_component_request')->get_post('goods_id');
        $isSelectedAll = kernel::single('base_component_request')->get_post('isSelectedAll');
        $visibility = $hide=='true' ? 'false' : 'true';
        $hide_zh = $hide=='true' ? $this->app->_('隐藏') : $this->app->_('显示');
        $products_id_list = array();

        $this->begin('index.php?app=ome&ctl=admin_goods&act=index');
        $goodsModel = $this->app->model('goods');
        $productsModel = $this->app->model('products');
        if ($isSelectedAll!='_ALL_' && $goods_id) {
            $filter = array('goods_id'=>$goods_id);
            
        }elseif ($isSelectedAll=='_ALL_') {
            $filter = array();
        }else{
            $this->end(false,$this->app->_('请选择商品!'));
        }
        $data['visibility'] = $visibility;
        $result = $goodsModel->update($data,$filter);
        if (!$result) {
            $this->end(false,$this->app->_($hide_zh.'失败'));
        }

        $result = $productsModel->update($data,$filter);
        if (!$result) {
            $this->end(false,$this->app->_($hide_zh.'失败'));
        }
        #只记录具体的goods_id，全部隐藏的不记录（全部隐藏的功能,可能有bug）
        if(!empty($filter['goods_id'])){
            if($_type == 'true'){
                $type = 'goods_hide@ome';
                $memo = '商品隐藏';
            }else{
                $type = 'goods_show@ome';
                $memo = '商品显示';
            }
            #批量隐藏或批量显示后，记日志
            foreach($filter['goods_id'] as $_goods_id){
                $opObj  = &app::get('ome')->model('operation_log');
                $opObj->write_log($type, $_goods_id, $memo);
            }
        }
        $this->end(true,$this->app->_($hide_zh.'成功'));
    }

    function toSerial(){
        $this->begin('index.php?app=ome&ctl=admin_goods&act=index');
        if($_GET['status'] && $_GET['status']=='true'){
            $data['serial_number'] = 'true';
        }else{
            $data['serial_number'] = 'false';
        }

        if($_POST['isSelectedAll'] && $_POST['isSelectedAll'] == '_ALL_'){
            $filter = array();
        }elseif($_POST['goods_id'] && is_array($_POST['goods_id'])){
            $filter = array('goods_id'=>$_POST['goods_id']);
        }else{
            $this->end(false, app::get('omeauto')->_('操作失败。'));
        }

        $goodsObj = app::get('ome')->model('goods');
        $goodsObj->update($data,$filter);
        $this->end(true, app::get('ome')->_('操作成功。'));
    }

    function view_gimage($image_id){
        $oImage = $this->app->model('image');
        $this->pagedata['image_id'] = $image_id;
        $this->display('goods/detail/img/view_gimages.html');        
    }

     function import(){      
		$oIo = kernel::servicelist('omecsv_io');
		if(!empty($oIo)){
			$this->pagedata['thisUrl'] = 'index.php?app=omecsv&ctl=admin_to_import&act=treat';
			$import = new omecsv_ctl_admin_import($this);
			$_GET['ctler']='ome_mdl_goods';
			$_GET['add']='ome';
		}else{
			$this->pagedata['thisUrl'] = 'index.php?app=ome&ctl=admin_goods&act=index';
			$import = new desktop_finder_builder_import($this);
		}
        
        $oGtype = $this->app->model('goods_type');
        $this->pagedata['gtype'] = $oGtype->getList('type_id,name');
        echo $this->page('admin/goods/download.html');
        echo "<div class=\"tableform\">";
        $import->main();
        echo "</div></div></div>";
    }
   
    /**
     * 成本价导入
     *
     * @return void
     * @author 
     **/
    function cost_import(){
        $oIo = kernel::servicelist('omecsv_io');
        if(!empty($oIo)){
            $this->pagedata['thisUrl'] = 'index.php?app=omecsv&ctl=admin_to_import&act=treat&ctler=ome_mdl_goods&add=ome&acti='.$_GET['acti'];
            $import = new omecsv_ctl_admin_import($this);
            $_GET['ctler']='ome_mdl_goods';
            $_GET['add']='ome';
        }else{
            $this->pagedata['thisUrl'] = 'index.php?app=ome&ctl=admin_goods&act=index&ctler=ome_mdl_goods&add=ome&acti='.$_GET['acti'];
            $import = new desktop_finder_builder_import($this);
        }
        
        $oGtype = $this->app->model('goods_type');
        $this->pagedata['gtype'] = $oGtype->getList('type_id,name');
        echo "<div class=\"tableform\">";
        $import->main();
        echo "</div></div></div>";
    }
    
    /**
     * 导出成本价模板
     *
     * @return void
     * @author 
     **/
    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=成本价模板.csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $oObj = &$this->app->model('goods');
        $title = $oObj->exportCostTemplate();
        echo '"'.implode('","',$title).'"';
    }

}
