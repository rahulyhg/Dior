<?php
class ome_ctl_admin_branch extends desktop_controller{
    var $name = "仓库管理";
    var $workground = "goods_manager";

    function index(){
       if($this->app->getConf('ome.branch.mode')=='single' && $this->app->model('branch')->getList("branch_id")){

           $this->editbranch('1',false);
           exit;

           $actions = array(
                    //array('label'=>'添加货位','href'=>'index.php?app=ome&ctl=admin_branch&act=addpos'),
                );
       }else{
           $actions = array(
                    array('label'=>'添加仓库','href'=>'index.php?app=ome&ctl=admin_branch&act=addbranch&singlepage=false&finder_id='.$_GET['finder_id'],'target'=>'_blank'),
                    //array('label'=>'添加货位','href'=>'index.php?app=ome&ctl=admin_branch&act=addpos'),
                );
           /*
             * 获取操作员管辖仓库
             */
            $oBranch = &app::get('ome')->model('branch');
            $is_super = kernel::single('desktop_user')->is_super();
            if (!$is_super){
                $branch_ids = $oBranch->getBranchByUser(true);
                if ($branch_ids){
                    $filter['branch_id'] = $branch_ids;
                }else{
                    $filter['branch_id'] = 'false';
                }
            }
       }
       $params = array(
            'title'=>'仓库管理',
            'actions'=>$actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'base_filter' => $filter,
         );
       $this->finder('ome_mdl_branch',$params);
    }
     /*
     * 仓库添加
     *
     * @param int $branch_id
     *
     */
    function addbranch(){
        $oBranch = &$this->app->model("branch");

        if($_POST){
            $this->begin('index.php?app=ome&ctl=admin_branch&act=index');
            $_POST['stock_threshold'] = $_POST['stock_threshold'] ? $_POST['stock_threshold'] :0;
            $data = $_POST;
            unset($data['area_conf']);
            if (!$data['branch_id'] && $_POST['wms_id']=='') {
                $this->end(false,'请选择仓库对应WMS!');
            }
            kernel::single("ome_stock_do")->save_stock_safe_info($data);
            $areaObj = &$this->app->model('branch_area');

            if ($data['attr']=='false' && $data['shop_config']) {
                $this->end(false,'线下仓库不能设置对应店铺！');
            }

            //一个主仓只有一个残仓
            if($data['branch_type'] == 'damaged'){
                if(!empty($data['branch_id'])){
                    if(!empty($data['main_branch'])){
                        $damaged = $oBranch->db->selectrow("SELECT count(*) as num FROM `sdb_ome_branch` WHERE  `type` = 'damaged' AND `parent_id` =".$data['main_branch']." AND `branch_id` <> ".$data['branch_id']);
                    }else{
                        $damaged['num'] = 0;
                    }
                }else{
                    if(!empty($data['main_branch'])){
                        $damaged = $oBranch->db->selectrow("SELECT count(*) as num FROM `sdb_ome_branch` WHERE  `type` = 'damaged' AND `parent_id` =".$data['main_branch']);
                    }else{
                        $damaged['num'] = 0;
                    }
                }
                if($damaged['num'] >= 1){
                    $this->end(false, app::get('base')->_('该主仓下已存在一个残仓，请重新选择主仓！'));
                }
                $data['attr'] = 'false';
            }

            if ($_POST['branch_type']) {
                if($_POST['branch_type'] == 'main'){
                    $data['type'] = $_POST['branch_type'];
                    $data['parent_id'] = 0;
                }else{
                    $data['type'] = $_POST['branch_type'];
                    $data['attr'] = 'false';
                    $data['is_deliv_branch'] = 'false';
                    $data['parent_id'] = $_POST['main_branch'];
                    #残仓售后仓wms_id复用主仓
                }
            }


            $oldBrancn = $oBranch->dump(array('branch_bn'=>$data['branch_bn']),'branch_id,branch_bn');
            if(($oldBrancn['branch_bn'] && !$data['branch_id']) || ($oldBrancn['branch_id'] && $oldBrancn['branch_id']!=$data['branch_id'])){
                $this->end(false, app::get('base')->_('仓库编号重复'));
            }

            $checkname_id = $oBranch->namecheck(trim($_POST['name']));
            if(!$data['branch_id'] && $checkname_id){
                $this->end(false, app::get('base')->_('仓库名称已经存在'));
            }
            $data['storage_code'] = $_POST['storage_code'];//库内存放点编号
            $oBranch->save($data);
            # 存储仓库与店铺的关联关系
           
                
            ome_shop_branch::update_relation($data['branch_bn'], $data['shop_config'],$data['branch_id']);
           
            //增加仓库保存后的扩展
            foreach(kernel::servicelist('ome.branch') as $o){
                if(method_exists($o,'after_branch_save')){
                    $o->after_branch_save($data);
                }
            }

            $this->end(true, app::get('base')->_('保存成功'));
       }

        //添加仓库类型
        $cols = $oBranch->_columns();
        $type = $cols['type']['type'];
        $this->pagedata['type'] = $type;

        #第三方仓储列表
        $wms_list = kernel::single('ome_branch')->getWmsChannelList();
        if (count($wms_list)==0) {
             $this->singlepage("admin/system/nowmsset.html");
             exit;
        }
        $this->pagedata['wms_list'] = $wms_list;
        //
       # 绑定店铺
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->getList('*');
        $this->pagedata['shop'] = $shop;

        $safe_time = array(array(0,'0:00'),array(1,'1:00'),array(2,'2:00'),array(3,'3:00'),array(4,'4:00'),
                           array(5,'5:00'),array(6,'6:00'),array(7,'7:00'),array(8,'8:00'),array(9,'9:00'),
                           array(10,'10:00'),array(11,'11:00'),array(12,'12:00'),array(13,'13:00'),array(14,'14:00'),
                           array(15,'15:00'),array(16,'16:00'),array(17,'17:00'),array(18,'18:00'),array(19,'19:00'),
                           array(20,'20:00'),array(21,'21:00'),array(22,'22:00'),array(23,'23:00'));
        $this->pagedata['safe_time'] = $safe_time;
        $this->pagedata['title'] = '添加仓库';

        $options['owner'] = array('1'=>'自建仓库','2'=>'第三方仓库(自有仓导入方式进行发货)');
        $this->pagedata['options'] = $options;

        $this->singlepage("admin/system/branch.html");
    }

    /*
     * 仓库编辑
     *
     * @param int $branch_id
     *
     */
    function editbranch($branch_id=null, $singlepage=false){
        $oBranch = &$this->app->model("branch");
        $oBranch_area = &$this->app->model("branch_area");
        $oRegions = &app::get('eccommon')->model("regions");

        $branch = $oBranch->dump(array('branch_id'=>$branch_id), '*');
        $area_conf = unserialize($branch['area_conf']);
        $areas = $area_conf['areaGroupId'];
        $areas_name = $area_conf['areaGroupName'];

        //仓库设置
        $cols = $oBranch->_columns();
        $type = $cols['type']['type'];
        $this->pagedata['type'] = $type;

        #第三方仓储列表
        $this->pagedata['wms_list'] = kernel::single('ome_branch')->getWmsChannelList();
        $wms_disabled = false;
        //主仓
        if(!empty($branch_id)){
            $p_id = $oBranch->dump(array('branch_id'=>$branch_id),'parent_id');
            $parentItem = $oBranch->dump(array('branch_id'=>$p_id['parent_id']),'branch_id,name');
            #判断是否仓库是否已有单据，如果有不可以切换wms
            #采购单发货单采购退货单调拔单出入库单
            $oPurchase = &app::get('purchase')->model('po');
            $oReturn_purchase = &app::get('purchase')->model('returned_purchase');
            $oDelivery = &app::get('ome')->model('delivery');
            $oIso = &app::get('taoguaniostockorder')->model('iso');
            $oAppropriation = &app::get('taoguanallocate')->model('appropriation');
            $purchase = $oPurchase->dump(array('branch_id'=>$branch_id),'branch_id');
            $return_purchase = $oReturn_purchase->dump(array('branch_id'=>$branch_id),'branch_id');
            $delivery = $oDelivery->dump(array('branch_id'=>$branch_id),'branch_id');
            $iso = $oIso->dump(array('branch_id'=>$branch_id),'branch_id');
            $appropriation = $oAppropriation->dump(array('branch_id'=>$branch_id),'branch_id');
            if ($purchase || $return_purchase || $delivery || $iso || $appropriation) {
                $wms_disabled = true;
            }
            #若是主仓，查看是否作为父仓，如果是父仓不允许切换为售后仓或残仓
            if ($branch['type'] == 'main') {
                $branch_main = $oBranch->getlist('branch_id',array('parent_id'=>$branch_id),0,-1);
                if (count($branch_main)>0) {
                    $branch_main_disabled = true;
                }
            }

        }
        $this->pagedata['branch_main_disabled'] = $branch_main_disabled;
        $this->pagedata['wms_disabled'] = $wms_disabled;
        $sql = "SELECT * FROM `sdb_ome_branch` as s WHERE  type='main' and attr='true' and ( select count(*) from sdb_ome_branch where `type` = 'damaged' and parent_id=s.branch_id) = 0";
        $main_branchs = $oBranch->db->select($sql);
        if(!empty($parentItem)){
            array_push($main_branchs,$parentItem);
        }
        if(!empty($main_branchs)){
            foreach($main_branchs as $v){
                $main_branch[$v['branch_id']] = $v['name'];
            }
        }
        $this->pagedata['main_branch'] = $main_branch;

        # 绑定的店铺
        $shopModel = app::get('ome')->model('shop');
        $shop = $shopModel->getList('*');
        $this->pagedata['shop'] = $shop;

        # 仓库关联的店铺
        $shop_branchs = app::get('ome')->getConf('shop.branch.relationship');
        $shop_bns = array();
        if ($shop_branchs){
            foreach ( $shop_branchs as $shop=>$branchs ){
                if ( in_array($branch['branch_bn'], $branchs) ){
                    $shop_bns[] = strval($shop);
                }
            }
        }
        $this->pagedata['shop_bns'] = $shop_bns;


        $safe_time = array(array(0,'0:00'),array(1,'1:00'),array(2,'2:00'),array(3,'3:00'),array(4,'4:00'),
                           array(5,'5:00'),array(6,'6:00'),array(7,'7:00'),array(8,'8:00'),array(9,'9:00'),
                           array(10,'10:00'),array(11,'11:00'),array(12,'12:00'),array(13,'13:00'),array(14,'14:00'),
                           array(15,'15:00'),array(16,'16:00'),array(17,'17:00'),array(18,'18:00'),array(19,'19:00'),
                           array(20,'20:00'),array(21,'21:00'),array(22,'22:00'),array(23,'23:00'));

        $this->pagedata['safe_time'] = $safe_time;
        $this->pagedata['branch']    = $branch;
        $this->pagedata['title']     = '编辑仓库';

        $options['owner'] = array('1'=>'自建仓库','2'=>'第三方仓库');
        $this->pagedata['options'] = $options;

        if ($singlepage==false)
            $this->page("admin/system/branch.html");
        else
            $this->singlepage("admin/system/branch.html");
    }

    function addpos($branch_id=0){
        $oBranch_pos = &$this->app->model("branch_pos");
        $oBranch = &$this->app->model("branch");
        $branch_list=$oBranch->Get_branchlist();
        if($_POST){
            $this->begin('index.php?app=ome&ctl=admin_branch&act=addpos&p[0]='.$_POST['branch_id']);
            $_POST['store_position'] = strtoupper($_POST['store_position']);
            $branch_pos = $oBranch_pos->dump(array('store_position'=>$_POST['store_position'],'branch_id'=>$_POST['branch_id']), 'pos_id');
            if($branch_pos['pos_id']){
                $this->end(false, app::get('base')->_('货位已存在'));
            }
            $_POST['stock_threshold'] = !$_POST['stock_threshold'] ? 0 : intval($_POST['stock_threshold']);

            $oBranch_pos->save($_POST);

            $this->end(true, app::get('base')->_('保存成功'),'index.php?app=ome&ctl=admin_branch_pos&act=index');
        }
        $this->pagedata['branch_id'] = $branch_id;
        $this->pagedata['branch_list'] = $branch_list;

        //获取仓库模式
        //$branch_mode = &app::get('ome')->getConf('ome.branch.mode');
        //$this->pagedata['branch_mode'] = $branch_mode;

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
          $branch_list_byuser = $oBranch->getBranchByUser();
        }
        $this->pagedata['branch_list_byuser'] = $branch_list_byuser;
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['title'] = '添加货位';
        $this->singlepage("admin/system/branch_pos.html");
    }


    /*
    * 没有残仓的主仓
    *
    */
    function get_branch_type($branch_type,$branch_bn,$wms_id){
        $oBranch = &$this->app->model("branch");
        if(!empty($branch_bn)){
            $p_id = $oBranch->dump(array('branch_bn'=>$branch_bn),'branch_id,parent_id');
            $parentItem = $oBranch->dump(array('branch_id'=>$p_id['parent_id']),'branch_id,name');
        }
        if($branch_type == 'damaged'){
            $main_branchs = array();
            if(!empty($p_id['branch_id'])){
                $b_sql = "SELECT * FROM `sdb_ome_branch` as s WHERE  type='main' and attr='true' and ( select count(*) from sdb_ome_branch where `type` = 'damaged' and parent_id=s.branch_id) = 0  AND `branch_id` <> ".$p_id['branch_id'];
            }else{
                $b_sql = "SELECT * FROM `sdb_ome_branch` as s WHERE  type='main' and attr='true' and ( select count(*) from sdb_ome_branch where `type` = 'damaged' and parent_id=s.branch_id) = 0";
            }
            $b_sql.=" AND wms_id=".$wms_id;
           
            $main_branchs = $oBranch->db->select($b_sql);
            if(!empty($parentItem)){
                array_push($main_branchs,$parentItem);
            }
            foreach($main_branchs as $v){
                $branch_id = $v['branch_id'];
                $branch_name = $v['name'];
                $h .= '<option value="'.$branch_id.'"';
                if($p_id['parent_id'] == $branch_id)
                    $h .= ' SELECTED';
                $h .='>'.$branch_name.'</option>';
            }
            $html = <<<EOF
            <option>  </option>
$h
EOF;
        }else{
            $main_branchs = array();
            //主仓
            if(!empty($p_id['branch_id'])){
                $sql = "SELECT `branch_id`,`name` FROM `sdb_ome_branch` WHERE `type`='main' AND `attr`='true' AND `branch_id` <> ".$p_id['branch_id'];
            }else{
                $sql = "SELECT `branch_id`,`name` FROM `sdb_ome_branch` WHERE `type`='main' AND `attr`='true'";
            }
            $sql.=" AND wms_id=".$wms_id;
            
            $main_branchs = $oBranch->db->select($sql);

            if(!empty($main_branchs)){
                foreach($main_branchs as $v){
                    $branch_id = $v['branch_id'];
                    $branch_name = $v['name'];
                    $h .= '<option value="'.$branch_id.'">'.$branch_name.'</option>';
                }

            $html = <<<EOF
            <option>  </option>
$h
EOF;
            }
        }
        if(empty($main_branchs)){
            echo 'false';
        }else{
            echo $html;
        }
    }

    /**
     *选择前端物流公司
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function ajax_select_branch($wms_id)
    {
        
        $this->page("admin/system/wms_branch.html");
        
    }

    
    /**
     * 获取WMS仓库
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function ajax_wms_branch($wms_id)
    {
        
        // $result = kernel::single('middleware_wms_request', $wms_id)->get_warehouse_list($sdf,true);
        $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->branch_getlist(null);

        $wms_list = $result['data'];
        echo json_encode($wms_list);
    }
   
   
   /**
    * Short description.
    * @param   type    $varname    description
    * @return  type    description
    * @access  public
    * @author cyyr24@sina.cn
    */
   function ajax_wms_corp()
   {
       $wms_id = 16;
       //$result = kernel::single('middleware_wms_request', $wms_id)->get_logistics_list($sdf,true);
       $result = kernel::single('erpapi_router_request')->set('wms',$wms_id)->logistics_getlist($sdf);
   }
   
}

?>
