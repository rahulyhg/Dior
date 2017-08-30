<?php
class omepkg_ctl_admin_pkg extends desktop_controller{

    var $name = "捆绑商品";
    var $workground = "pkg";

    function index(){
        #增加捆绑商品类型导出权限
        $is_export = kernel::single('desktop_user')->has_permission('goods_export');
        $params = array(
            'title'=>'商品捆绑',
            'actions'=>array(
                array('label'=>'捆绑','href'=>'index.php?app=omepkg&ctl=admin_pkg&act=showAddPackage','target'=>'_blank'),
                array('label'=>'导出模板','href'=>'index.php?app=omepkg&ctl=admin_pkg&act=exportTemplate','target'=>'_blank'),
                array('label'=>app::get('omepkg')->_('删除'),'icon' => 'del.gif', 'confirm' =>'确定删除选中项？','submit'=>'index.php?app=omepkg&ctl=admin_pkg&act=delPkgGoods',),
             ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>$is_export,
            'use_buildin_import'=>true,
            'use_buildin_filter'=>false,
        );
        $this->finder('omepkg_mdl_pkg_goods',$params);
    }
    #删除捆绑商品
    public function delPkgGoods(){
        $this->_request = kernel::single('base_component_request');
        $this->begin('index.php?app=omepkg&ctl=admin_pkg&act=index');
        $obj_pkg_goods = $this->app->model('pkg_goods');
        $isSelectedAll = $this->_request->get_post('isSelectedAll');
    
        $goods_id = $this->_request->get_post('goods_id');
        $pkg_bn = $this->_request->get_post('pkg_bn');
        $name = $this->_request->get_post('name');
    
        if(!empty($goods_id)){
            $filter['goods_id'] = $goods_id;
        }elseif($isSelectedAll == '_ALL_'){
            #直接全部删除
            if(empty($pkg_bn) && empty($name)){
                $filter = array();
            }else{
                #按照捆绑商品货号筛选全部删除
                if(!empty($pkg_bn)){
                    $filter['filter_sql'] = ' pkg_bn like \'%'.$pkg_bn.'%\'';
                }
                #按照捆绑商品名称筛选全部删除
                elseif(!empty($name)){
                    $filter['filter_sql'] = ' name like \'%'.$name.'%\'';
                }
                #获取满足条件的goods_id
                $arrgoods_id = $obj_pkg_goods->getList('goods_id',$filter,0,-1);
                unset($filter);
                foreach($arrgoods_id as $id){
                    $filter['goods_id'][] = $id['goods_id'];
                }
            }
        }else{
            $this->end(false,$this->app->_('请选择赠品!'));
        }
        $recle =  kernel::single('desktop_system_recycle');
        $rs = $recle->dorecycle('omepkg_mdl_pkg_goods',$filter);
        if($rs){
            $this->end(true,'删除成功！');
        }else{
            $this->end(false,'删除失败！');
        }
    }    
    function getEditProducts($po_id){
        if ($po_id == ''){
            $po_id = $_POST['p[0]'];
        }
        
        $pObj = &app::get('ome')->model('products');
        $piObj = &$this->app->model('pkg_product');
        $rows = array();
        $items = $piObj->getList('product_id,bn,name,pkgnum',array('goods_id'=>$po_id),0,-1);
        foreach ($items as $key => $value) {
            $pids[] = $value['product_id'];
            $items[$key]['visibility'] = &$list[$value['product_id']]['visibility'];
        }
        //unset($items);

        # 判断是否是隐藏商品
        if ($pids) {
            $plist = $pObj->getList('product_id,visibility',array('product_id'=>$pids),0,-1);
            foreach ($plist as $key => $value) {
				$list[$value['product_id']]['visibility'] = $value['visibility'];
            }
            unset($plist);   
        }

        echo json_encode($items);
    }
    
    
    function showAddPackage(){
		$this->pagedata['title'] = '添加捆绑商品';
        $this->singlepage('admin/package/addPackage.html');
    }
    
    function edit($po_id){
        $this->begin('index.php?app=omepkg&ctl=admin_pkg&act=index');
        if (empty($po_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        $goods = &$this->app->model('pkg_goods');
        $product = &$this->app->model('pkg_product');
        $data['po_items'] = $product->getAllProduct($po_id);
        $data['po'] = $goods->getgoods($po_id);
        
        $this->pagedata['po_items'] = $data['po_items'];
		$this->pagedata['po'] = $data['po'][0];
		$this->pagedata['title'] = '编辑捆绑商品';
        $this->singlepage("admin/package/editPackage.html");
    }
    
    
    function updatepkg(){
        $this->begin('index.php?app=omepkg&ctl=admin_pkg&act=index');
        if(empty($_POST['goods_id'])){
            $this->end(false,'操作出错，请重新操作');
        }
        $goods = &$this->app->model('pkg_goods');
        $product = &$this->app->model('pkg_product');
        $pObj = &app::get('ome')->model('products');
        
        if(empty($_POST['at'])){
            $this->end(false, '捆绑商品不能为空');
        }
        /*
        if(count($_POST['at']) < 2){
            $this->end(false, '捆绑的商品不能少于两个。');
        }*/
        
        if(empty($_POST['name']) || empty($_POST['weight'])){
            $this->end(false,'绑定信息必须填写');
        }
        if($_POST['weight'] < 0){
            $this->end(false,'重量输入错误');
        }
    
        foreach ($_POST['at'] as $val){
            if (count($_POST['at']) == 1){
                if ($val <2) $this->end(false,'只有一种货品时，数量必须大于1');
            }else {
                if ($val < 1) $this->end(false,'数量必须大于0');
            }
        }
        
        $data['name'] = $_POST['name'];
        $data['weight'] = $_POST['weight'];

        
        $log_po_items = $product->getAllProduct($_POST['goods_id']);
        
        
        $log_memo = array(
            
            'po_items'=>$log_po_items
        
        );
        $log_memo = serialize($log_memo);
        $goods->save_log($_POST['goods_id'],$data['name'],$log_memo);
        $goods->update($data,array('goods_id'=>$_POST['goods_id']));
        $product->delete(array('goods_id'=>$_POST['goods_id']));
            foreach($_POST['at'] as $k=>$v){
                $p = $pObj->dump($k, 'bn,name');
                $tmp['goods_id'] = $_POST['goods_id'];
                $tmp['pkgnum'] = $v;
                $tmp['bn'] = $p['bn'];
                $tmp['product_id'] = $k;
                $tmp['name'] = $p['name'];
                $product->insert($tmp);
                $tmp = null;
            }
        
        $this->end(true,'修改成功。');
        
    }
    
    function save(){
        $this->begin('index.php?app=omepkg&ctl=admin_pkg&act=index');
        if(empty($_POST['at'])){
            $this->end(false, '捆绑商品不能为空。');
        }
        /*
        if(count($_POST['at']) < 2){
            $this->end(false, '捆绑的商品不能少于两个。');
        }*/
        
        if(empty($_POST['name']) || empty($_POST['weight']) || empty($_POST['pkg_bn'])){
            $this->end(false,'绑定信息必须填写。');
        }
        
        foreach ($_POST['at'] as $val){
            if (count($_POST['at']) == 1){
                if ($val <2) $this->end(false,'只有一种货品时，数量必须大于1');
            }else {
                if ($val < 1) $this->end(false,'数量必须大于0');
            }
        }
        
        $goods = &$this->app->model('pkg_goods');
        $product = &$this->app->model('pkg_product');
        $goods_id = $goods->checkPkgBn($_POST['pkg_bn']);
        $pObj = &app::get('ome')->model('products');
        
        if(!empty($goods_id)){
            $this->end(false,'绑定信息货号已经存在，请重新填写。');
        }
        
        foreach(kernel::servicelist('ome.product') as $name=>$object){
            if(method_exists($object, 'checkProductByBn')){
                $checkBn = $object->checkProductByBn($_POST['pkg_bn']);
                if($checkBn){
                    $this->end(false,app::get('base')->_('您所填写的货号已被其它商品模块使用！'));
                    break;
                }
            }
        }

        $data['name'] = $_POST['name'];
        $data['weight'] = $_POST['weight'];
        $data['pkg_bn'] = $_POST['pkg_bn'];
        
        $goods->save($data);
        if($data['goods_id']){
            foreach($_POST['at'] as $k=>$v){
                $p = $pObj->dump($k, 'bn,name');
                $tmp['goods_id'] = $data['goods_id'];
                $tmp['pkgnum'] = $v;
                $tmp['bn'] = $p['bn'];
                $tmp['product_id'] = $k;
                $tmp['name'] = $p['name'];
                $product->insert($tmp);
                $tmp = null;
            }
            
            $this->end(true,'绑定成功。');
        }else{
            $this->end(false,'绑定失败。');
        }
    }
    
    function findProduct(){
        $base_filter = array();
        if (!isset($_POST['visibility'])) {
            $base_filter['visibility'] = 'true';
        }elseif(empty($_POST['visibility'])){
            unset($_POST['visibility']);
        }

        $object_method = array('count'=>'count','getlist'=>'getlist');
        if ($_POST['branch_id']) {
            $base_filter['product_group'] = true;
            $object_method = array('count'=>'countAnother','getlist'=>'getListAnother');
        }
        
        $params = array(
            'title'=>'商品列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'base_filter' => $base_filter,
            'object_method'=>$object_method,
        );
        $this->finder('ome_mdl_products', $params);
        
    }
    
    function getProducts(){
        $pro_id = $_POST['product_id'];
        
        $pro_bn= $_GET['bn'];
        
        $pro_name= $_GET['name'];
        
        if (is_array($pro_id)){
            if ($pro_id[0] == "_ALL_"){
                $filter = '';
            }else {
                $filter['product_id'] = $pro_id;
            }
        }
        
        if($pro_bn){
           
           $filter = array(
               'bn'=>$pro_bn
           );           
        }
        
        if($pro_name){
            $filter = array(
               'name'=>$pro_name
           );       
        }        

        $pObj = &app::get('ome')->model('products');
        $filter['use_like'] = 1;
        $data = $pObj->getList('product_id,bn,name,visibility',$filter,0,-1);

        if (!empty($data)){
            foreach ($data as $k => $item){
                $item['num'] = null;
                //$item['price'] = $this->app->model('po')->getPurchsePrice($item['product_id'], 'asc');
                $rows[] = $item;
            }
        }

        echo json_encode($rows);
    }
    //将ome_mdl_orders中的方法放到此处
    function findPkgProduct(){
        $params = array(
                        'title'=>'捆绑商品列表',
                        'use_buildin_new_dialog' => false,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_recycle'=>false,
                        'use_buildin_export'=>false,
                        'use_buildin_import'=>false,
                        'use_buildin_filter'=>true,
                    );
        $this->finder('omepkg_mdl_pkg_goods', $params);
    }
    //将ome_mdl_orders中的方法放到此处
    function getPkgProducts(){
        $goods_id = $_POST['goods_id'];
        if ($goods_id){
            $filter['goods_id'] = $goods_id;
        }else {
            exit('');
        }
        $pkgObj = &app::get('omepkg')->model('pkg_goods');
        $rows = $pkgObj->getList('*',$filter,0,-1);
        $pObj = &app::get('omepkg')->model('pkg_product');
        $list = array();
        if ($rows){
            foreach ($rows as $v){
                $data = $pObj->getList('product_id,bn,name,pkgnum',array('goods_id'=>$v['goods_id']),0,-1);
                $v['items'] = $data;
                $list[] = $v;
            }
        }
        echo "window.autocompleter_json=".json_encode($list);
    }
    //将ome_mdl_orders中的方法放到此处
    function getPkgGoods(){
        $goods_id = $_POST['goods_id'];
        if ($goods_id){
            $filter['goods_id'] = $goods_id;
        }else {
            exit('');
        }
        $pkgObj = &app::get('omepkg')->model('pkg_goods');
        $rows = $pkgObj->getList('*',$filter,0,-1);
        $list = array();
        if ($rows){
            foreach ($rows as $v){
                $v['product_id'] = 'pkg_'.$v['goods_id'];
                $v['spec_info'] = '捆绑';
                $v['bn'] = $v['pkg_bn'];
                $v['type'] = 'pkg';
                $v['num'] = '';
                $v['store'] = '--';
                $v['price'] = '';
                $list[] = $v;
            }
        }
        echo "window.autocompleter_json=".json_encode($list);
    }


    function show_history($log_id) {
        $logObj = &app::get('ome')->model('operation_log');
        $pkglog = $logObj->dump($log_id,'memo');
        $memo = unserialize($pkglog['memo']);
        $this->pagedata['pkg_data'] = $memo['po_items'];
        unset($pkglog);
        $this->singlepage('admin/package/history_log.html');
    }
    #导模板
    function exportTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $obj_pkg = &app::get('omepkg')->model('pkg_goods');
        $title = $obj_pkg->exportTemplate('template');
        echo '"'.implode('","',$title).'"';
    }
}
?>
