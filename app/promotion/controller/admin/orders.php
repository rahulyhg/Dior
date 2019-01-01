<?php
class promotion_ctl_admin_orders extends desktop_controller{
    
    private $source = array(
        array('source'=>'pc'),
        array('source'=>'wap'),
        array('source'=>'minishop'),
    );
    
    function index(){
        $this->title = '订单促销';        
   		$params = array(
            'title'=>$this->title,
            'use_buildin_recycle'=>true,
			 'actions'=>array(
                array('label'=>'添加规则','href'=>'index.php?app=promotion&ctl=admin_orders&act=add&finder_id='.$_GET['finder_id'],'target'=>'_blank'),
				/*array(
                    'label' => '应用订单',
                    'href' => 'index.php?app=promotion&ctl=admin_orders&act=doAction',
                    'target' => "dialog::{width:500,height:300,title:'应用订单'}",
                ),*/
             ),
        );
        $this->finder('promotion_mdl_orders',$params);
    }
	
	function doAction(){
		$this->display("admin/promotion/doAction.html");
	}
	
	function ajaxActionConditions(){
		kernel::single("promotion_process")->begin();
	}
	
	function getShop(){
		$objShop=app::get('ome')->model('shop');
		$arrShop=$objShop->db->select("SELECT shop_id,name FROM sdb_ome_shop WHERE shop_type='magento' OR shop_bn='dior_credit'");
		return $arrShop;
	}
	
	function add(){
		header("Cache-Control:no-store");
		$this->pagedata['arrShop']=$this->getShop();
        $this->pagedata['arrSource']=$this->source;
		$this->pagedata['return_url'] = app::get('desktop')->router()->gen_url(array('app'=>'promotion', 'ctl'=>'admin_orders', 'act'=>'get_goods_info'));
		$arrPromotion['shop_type']=1;
		$this->pagedata['arrPromotion']=$arrPromotion;
	    $this->singlepage('admin/promotion/orders.html');
	}
	
	function get_goods_info(){
		$data = $_POST['data'];
		$objProduct=app::get('ome')->model('products');
		$arrProdcuts=$objProduct->getList("bn,name",array('product_id'=>$data[0]));
		echo json_encode( array('name'=>$arrProdcuts[0]['name'],'bn'=>$arrProdcuts[0]['bn']) );
		// echo "<pre>";print_r($arrProdcuts);exit();
	}

	function edit(){
		$ojbPromotion=$this->app->model('orders');
		$rule_id=$_GET['p']['0'];
		$arrPromotion=$ojbPromotion->getList("*",array('rule_id'=>$rule_id));
		$arrPromotion=$arrPromotion[0];
		$arrPromotion['conditions_serialize']=unserialize($arrPromotion['conditions_serialize']);
		$arrPromotion['actions_serialize']=unserialize($arrPromotion['actions_serialize']);
		
		if ($service_conditions = kernel::servicelist('conditions_lists')){ 
			foreach($service_conditions as $object=>$instance){
				 if(method_exists($instance, 'getEditData')){
                     $this->pagedata[substr($object,21)."_edit_data"]=$instance->getEditData($arrPromotion['conditions_serialize'],$arrPromotion['conditions']);
				 }
            }
        }
		
		$this->pagedata['return_url'] = app::get('desktop')->router()->gen_url(array('app'=>'promotion', 'ctl'=>'admin_orders', 'act'=>'get_goods_info')); 
        if(isset($arrPromotion['actions_serialize']['gift']['nums'])) {
             foreach($arrPromotion['actions_serialize']['gift']['nums'] as $key=>$value) {
                $arrPromotion['actions_serialize']['gift']['extra_value'][$key] = $value . ':' . $arrPromotion['actions_serialize']['gift']['limit_nums'][$key];
            }
        }
        
        if(isset($arrPromotion['actions_serialize']['gift']['pkg_nums'])) {
             foreach($arrPromotion['actions_serialize']['gift']['pkg_nums'] as $key=>$value) {
                $arrPromotion['actions_serialize']['gift']['extra_pkg_value'][$key] = $value . ':' . $arrPromotion['actions_serialize']['gift']['pkg_limit_nums'][$key];
            }
        }
       
		$this->pagedata['arrPromotion']=$arrPromotion;//echo "<pre>";print_r($arrPromotion);exit();
		$this->pagedata['rule_id']=$arrPromotion['rule_id'];//
		$arrShop=$this->getShop();
		$arrSaveShop=explode(',',$arrPromotion['shop']);
		foreach($arrShop as $k=>$v){
			foreach($arrSaveShop as $key=>$value){
				if($v['shop_id']==$value){
					$arrShop[$k]['seleted']=1;
					break;
				}
			}
		}
		$this->pagedata['arrShop']=$arrShop;
        
        $arrSource=$this->source;
		$arrSaveSource=explode(',',$arrPromotion['source']);
		foreach($arrSource as $k=>$v){
			foreach($arrSaveSource as $key=>$value){
				if($v['source']==$value){
					$arrSource[$k]['seleted']=1;
					break;
				}
			}
		}
		$this->pagedata['arrSource']=$arrSource;
		$this->singlepage('admin/promotion/orders.html');
	}
	
	function toAdd(){
		$this->begin('');
		if(empty($_POST['shop'])){
            $this->end(false,'请选择店铺');
		}
        if(empty($_POST['source'])){
            $this->end(false,'请选择订单来源');
		}
		$strShop=implode(",",$_POST['shop']);
        $strSource=implode(",",$_POST['source']);
		
		
		$ojbPromotion=$this->app->model('orders');
		// 开始时间&结束时间
		$data=$_POST;
		$actions=$data['actions'];
		if(empty($actions)){
            $this->end( false,'请选择优惠方案');
		}
		$rule_id=$_POST['rule_id'];
		
        foreach ($data['_DTIME_'] as $val) {
            $temp['from_time'][] = $val['from_time'];
            $temp['to_time'][] = $val['to_time'];
        }
		$arrPromotion=array();
        $arrPromotion['from_time'] = strtotime($data['from_time'].' '. implode(':', $temp['from_time']));
        $arrPromotion['to_time'] = strtotime($data['to_time'].' '. implode(':', $temp['to_time']));
		
		if($arrPromotion['from_time'] >= $arrPromotion['to_time']){
            $this->end(false,app::get('gift')->_('开始时间不能晚于或等于结束时间'));
        }
		
		if ($service_conditions = kernel::servicelist('conditions_lists')){ 
			foreach($service_conditions as $object=>$instance){
				 if(method_exists($instance, 'checkData')){
                      if(!$instance->checkData($data,$msg)){
            		      $this->end( false,$msg);
					  }
				 }
            }
        }

		if ($service_actions = kernel::servicelist('actions_lists')){
            foreach($service_actions as $object=>$instance){
				 if(method_exists($instance, 'checkData')&&($object=="promotion_actions_".$actions)){
                      if(!$instance->checkData($data,$msg)){
            		      $this->end( false,$msg);
					  }
				 }
            }
        }
		//echo "<pre>";print_r($data);exit();
		$arrPromotion['rule_id']=$rule_id;
		$arrPromotion['name']=$data['rule']['name'];
		$arrPromotion['description']=$data['rule']['description'];
		$arrPromotion['status']=$data['rule']['status'];
		$arrPromotion['shop']=$strShop;
        $arrPromotion['source']=$strSource;
		$arrPromotion['conditions_serialize']=serialize($data['conditions']);
		$arrPromotion['actions_serialize']=serialize($data['actions_serialize']);
		
		$conditions=implode(",",$data['rule']['conditions']);
		
		$arrPromotion['conditions']=$conditions;
		$arrPromotion['actions']=$actions;
		$arrPromotion['shop_type']=$data['shop_type'];
		
        //header('Content-Type:text/jcmd; charset=utf-8');
		if($ojbPromotion->save($arrPromotion)){
        	$this->end(true,'添加成功。');
			//echo '{success:"添加成功",_:null,goods_id:"12"}';
		}else{
            $this->end( false,'添加失败');
		}
	}
	
    function addRegions()
    {
        $objRegion = app::get('eccommon')->model('regions');
        $arrRegions = $objRegion->getList('region_id,local_name,p_region_id', array('region_grade'=>'1'));
        
        if(!empty($_GET['role'])) {
            $edit = true;
            $role = explode(',', $_GET['role']);
            $role = array_flip($role);
        }
        
        foreach ($arrRegions as $row) {
            $arr = array();
            $province_check = false;
            $arr = $objRegion->getList('region_id,local_name', array('p_region_id'=>$row['region_id']));
            if($edit) {
                foreach($arr as $k=>&$v) {
                    if(isset($role[$v['region_id']])) {
                        $v['checked'] = true; 
                        $province_check = true;
                    }
                }
            }
            $data[] = array(
                'province' => $row['local_name'],
                'province_id' => $row['region_id'],
                'province_check' => $province_check,
                'citys' =>  $arr,
            );
        }
        
        $this->pagedata['regions'] = $data;//
        $this->display("admin/promotion/regions.html");
        
    }
    
    function createRole()
    {
        $citys = $_POST['citys'];
        $code = 'Fail';
        if(empty($citys)) {
            $msg = '请选择地域';
        }else{
            $ids = array();
            foreach($citys as $k=>$city) {
                $arr = explode('_', $city);
                $ids[] = $arr[0];
                if($k <= 6) {
                    $local_name .= $arr['1'] . ',';
                }
            }
            
            $code = 'SUCC';
            $local_name = trim($local_name, ',') . '等' . count($citys) . '个地域';
            //echo "<pre>";print_r($ids);print_r($local_name);exit;
        }
        $data = array(
            'code' => $code,
            'msg' => $msg,
            'ids' => $ids,
            'local_name' => $local_name,
        );
        echo json_encode($data);
    }
    
	///////////////////////////////new
	function getEditProducts($rule_id){
        if ($gift_id == ''){
            $gift_id = $_POST['p[0]'];
        }

        $pObj = app::get('ome')->model('products');
        $giftProductObj = $this->app->model('tbgift_product');
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

        $pObj = app::get('ome')->model('products');
        $filter['use_like'] = 1;
        $data = $pObj->getList('product_id,bn,name,visibility',$filter,0,-1);

        if (!empty($data)){
            foreach ($data as $k => $item){
				$item['nums']=1;
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
        $pObj = app::get('omepkg')->model('pkg_goods');
        $data = $pObj->getList('goods_id as product_id,pkg_bn bn ,name,weight',$filter,0,-1);
        
        if (!empty($data)){
            foreach ($data as $k => $item){
				$item['nums']=1;
                $rows[] = $item;
            }
        }
        echo json_encode($rows);
    }    
}