<?php
class ome_ctl_admin_need_supply extends desktop_controller{
    var $name = "采购管理";
    var $workground = "purchase_manager";
    
	function index(){
        // 获取操作员管辖仓库
        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = $oBranch->getBranchByUser(true);
        $branch_id = intval($_GET['branch_id']);
        $supplier_id = intval($_POST['supplier_id']);
        $supplier_name= trim($_POST['supplier_name']);
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $filter['branch_id'] = $branch_ids;
            }else{
                $filter['branch_id'] = false;
            }
        }
		
		//var_dump($branch_ids);
        $actions = array(
            array(
                'label' => '设置安全库存',
                'href'=>'index.php?app=ome&ctl=admin_stock&act=batch_safe_store&branch_id='.$branch_id,
                'target' => "dialog::{width:700,height:400,title:'设置安全库存'}",
            ),
        );
            
        if($branch_id>0) {    
            $actions[] = array(
                'label' => '采购补货',
                'submit'=>'index.php?app=purchase&ctl=admin_purchase&act=createPurchase&p[0]=0&p[1]='.$branch_id,
                'target' => "_blank",
            );
        }

       $params = array(
            'title'=>'补货列表',
            'base_filter' => $filter,
            'actions' => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            'use_buildin_selectrow'=>true,
			'allow_detail_popup'=>false,
			'use_view_tab'=>true,
        );
		$this->finder('ome_mdl_supply_product',$params);
	}
	
	/**
     * 选项卡
     */
    public function _views(){
		
		// 获取操作员管辖仓库
        $oBranch = &app::get('ome')->model('branch');
        $oBranchProduct = &app::get('ome')->model('branch_product');
        
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true,false);
        }else{
            $branch_ids = $oBranch->getBranchByUser(true,true);
        }
        //echo('<pre>');var_dump($branch_ids);
        
        // 获取仓库的名称
        $filter['branch_id'] = $branch_ids;
        $filter['disabled'] = 'false';
        $branchs = $oBranch -> getList('branch_id,name',$filter);
        //echo('<pre>');var_dump($branchs);
		
		$sub_menu = array(
            0=>array('label'=>app::get('b2c')->_('所有仓库'),'optional'=>false,'filter'=>array('disabled'=>'false')),
        );
		
		for($i=0;$i<sizeof($branchs);$i++) {
			$sub_menu[] = array(
				'label'=>$branchs[$i]['name'],
				'optional'=>false,
				'filter'=>array('branch_id'=>$branchs[$i]['branch_id'])
			);
		}

        if(isset($_GET['optional_view'])) $sub_menu[$_GET['optional_view']]['optional'] = false;

        foreach($sub_menu as $k=>$v){
            $show_menu[$k] = $v;
			if(is_array($v['filter'])){
				$v['filter'] = array_merge(array('order_refer'=>'local'),$v['filter']);
			}else{
				$v['filter'] = array('order_refer'=>'local');
			}
			$show_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
			$show_menu[$k]['addon'] = $oBranchProduct->count($v['filter']);
			$show_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_need_supply&act=index&branch_id='.$v['filter']['branch_id'].'&view='.($k).(isset($_GET['optional_view'])?'&optional_view='.$_GET['optional_view'].'&view_from=dashboard':'');
        }
		//var_dump($show_menu);
        return $show_menu;
    }
    
    /**
     * 列表页面保存安全库存
     */
    public function save_safe_store(){
        $product_id = intval($_POST['product_id']);
        $branch_id = intval($_POST['branch_id']);
        $safe_store = intval($_POST['safe_store']);
        $this->app->model('branch_product')->update(
            array('safe_store'=>$safe_store),
            array('product_id'=>$product_id,'branch_id'=>$branch_id)
        );
    }
}