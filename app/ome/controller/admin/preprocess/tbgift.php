<?php
class ome_ctl_admin_preprocess_tbgift extends desktop_controller{
    var $name = "淘宝促销赠品管理";
    var $workground = "setting_tools";

    function index(){
       $this->finder('ome_mdl_tbgift_goods',array(
            'title' => '淘宝赠品管理',
            'actions'=>array(
                array('label'=>'赠品','href'=>'index.php?app=ome&ctl=admin_preprocess_tbgift&act=showAdd','target'=>'_blank'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>true,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
       ));
    }

    function showAdd(){
		$this->pagedata['title'] = '添加赠品';
        $this->singlepage('admin/preprocess/tbgift/addGift.html');
    }

    function edit($gift_id,$type){
        $this->begin('index.php?app=ome&ctl=admin_preprocess_tbgift&act=index');
        if (empty($gift_id)){
            $this->end(false,'操作出错，请重新操作');
        }
        $giftObj = &$this->app->model('tbgift_goods');
        $data['gift'] = $giftObj->getGiftById($gift_id);

		$this->pagedata['gift'] = $data['gift'][0];
		$this->pagedata['title'] = '编辑赠品';
		$this->pagedata['goods_type'] = $type;
        $this->singlepage("admin/preprocess/tbgift/editGift.html");
    }

    function getEditProducts($gift_id){
        if ($gift_id == ''){
            $gift_id = $_POST['p[0]'];
        }

        $pObj = &app::get('ome')->model('products');
        $giftProductObj = &$this->app->model('tbgift_product');
        $rows = array();
        $items = $giftProductObj->getList('product_id,bn,name',array('goods_id'=>$gift_id),0,-1);
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

    function save(){
        $this->begin('index.php?app=ome&ctl=admin_preprocess_tbgift&act=index');
        if(empty($_POST['pid'])){
            $this->end(false, '赠品对应的实际货品不能为空。');
        }

        if(empty($_POST['name']) || empty($_POST['gift_bn'])){
            $this->end(false,'赠品基本信息必须填写。');
        }

        $giftObj = &$this->app->model('tbgift_goods');
        $giftProductObj = &$this->app->model('tbgift_product');
        $goods_id = $giftObj->checkGiftByBn($_POST['gift_bn']);
        $pObj = &app::get('ome')->model('products');
        $pkgObj = &app::get('omepkg')->model('pkg_goods');

        if(!empty($goods_id)){
            $this->end(false,'赠品编码已经存在，请重新填写。');
        }        

        $data['name'] = $_POST['name'];
        $data['gift_bn'] = $_POST['gift_bn'];
        $data['goods_type'] = $_POST['goods_type'];
        $giftObj->save($data);
        #捆绑商品
        if($_POST['goods_type'] == 'bind'){
            if($data['goods_id']){
                foreach($_POST['pid'] as $k=>$v){
                    $pkg = $pkgObj->dump($v, 'pkg_bn,name');
                    $tmp['goods_id'] = $data['goods_id'];
                    $tmp['bn'] = $pkg['pkg_bn'];
                    $tmp['product_id'] = $v;
                    $tmp['name'] = $pkg['name'];
                    $giftProductObj->insert($tmp);
                    $tmp = null;
                }
            
                $this->end(true,'添加成功。');
            }else{
                $this->end(false,'添加失败。');
            }
        }elseif($_POST['goods_type'] == 'normal'){
        if($data['goods_id']){
            foreach($_POST['pid'] as $k=>$v){
                $p = $pObj->dump($v, 'bn,name');
                $tmp['goods_id'] = $data['goods_id'];
                $tmp['bn'] = $p['bn'];
                $tmp['product_id'] = $v;
                $tmp['name'] = $p['name'];               
                $giftProductObj->insert($tmp);
                $tmp = null;
            }

            $this->end(true,'添加成功。');
        }else{
            $this->end(false,'添加失败。');
        }}
    }

    function updateGift(){
        $this->begin('index.php?app=ome&ctl=admin_preprocess_tbgift&act=index');
        if(empty($_POST['goods_id'])){
            $this->end(false,'操作出错，请重新操作');
        }
        $giftObj = &$this->app->model('tbgift_goods');
        $giftProductObj = &$this->app->model('tbgift_product');
        $pObj = &app::get('ome')->model('products');
        $pkgObj = &app::get('omepkg')->model('pkg_goods');

        if(empty($_POST['pid'])){
            $this->end(false, '捆绑商品不能为空');
        }

        if(empty($_POST['name'])){
            $this->end(false,'赠品基本信息必须填写');
        }

        $data['name'] = $_POST['name'];

        $giftObj->update($data,array('goods_id'=>$_POST['goods_id']));
        $giftProductObj->delete(array('goods_id'=>$_POST['goods_id']));
        #添加捆绑商品
        if($_POST['goods_type'] == 'bind'){
            foreach($_POST['pid'] as $k=>$v){
                $pkg = $pkgObj->dump($v, 'pkg_bn,name');
                $tmp['goods_id'] = $_POST['goods_id'];
                $tmp['bn'] = $pkg['pkg_bn'];
                $tmp['product_id'] = $v;
                $tmp['name'] = $pkg['name'];
                $giftProductObj->insert($tmp);
                $tmp = null;
            }
            $this->end(true,'修改成功');
        }else{
            foreach($_POST['pid'] as $k=>$v){
                $p = $pObj->dump($v, 'bn,name');
                $tmp['goods_id'] = $_POST['goods_id'];
                $tmp['bn'] = $p['bn'];
                $tmp['product_id'] = $v;
                $tmp['name'] = $p['name'];
                $giftProductObj->insert($tmp);
                $tmp = null;
            }

        $this->end(true,'修改成功。');
        }
    }

    function findProduct(){
        $base_filter = array();
        if (!isset($_POST['visibility'])) {
            $base_filter['visibility'] = 'true';
        }elseif(empty($_POST['visibility'])){
            unset($_POST['visibility']);
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
                $rows[] = $item;
            }
        }

        echo json_encode($rows);
    }
    #捆绑商品
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
    #捆绑商品
    function getPkgProducts(){
        $pro_id = $_POST['product_id'];
        if (is_array($pro_id)){
            if ($pro_id[0] == "_ALL_"){
                $filter = '';
            }else {
                $filter['goods_id'] = $pro_id;
            }
        }
        $pObj = &app::get('omepkg')->model('pkg_goods');
        $data = $pObj->getList('goods_id as product_id,pkg_bn bn ,name,weight',$filter,0,-1);
        
        if (!empty($data)){
            foreach ($data as $k => $item){
                $rows[] = $item;
            }
        }
        echo json_encode($rows);
    }    
}

?>
