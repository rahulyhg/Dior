<?php
class wms_ctl_admin_stock extends desktop_controller{
    var $name = "库存查看";
    var $workground = "wms_center";
    
    /*
    function _views(){
        $sub_menu = $this->_views_stock();
        return $sub_menu;
    }*/
    function _views_stock(){

        $branch_productObj = &app::get('ome')->model('branch_product');
        $productObj = &app::get('ome')->model('products');
        $branch_productObj = &app::get('ome')->model('branch_product');
        $productObj = &app::get('ome')->model('products');

        $oBranch = &app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
            $branch_ids = $oBranch->getBranchByUser(true);
            if ($branch_ids){
                $base_filter['branch_id'] = $branch_ids;
            }else{
                $base_filter['branch_id'] = 'false';
            }
        }
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'),'optional'=>false,
                'href'=>'index.php?app=wms&ctl=admin_stock&act=index',

            )
        );

        $i=0;
        foreach($sub_menu as $k=>$v){
            if (!IS_NULL($v['filter'])){
                $v['filter'] = array_merge($v['filter'], $base_filter);
            }
            if($k==0){
                $sub_menu[$k]['addon']=$productObj->countAnother($base_filter);
            }else if($k==1){
                $sub_menu[$k]['addon']=$branch_productObj->countlist($base_filter);
            }

            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['href'] = $v['href'].'&view='.$i++;
        }
        return $sub_menu;
    }
    /**
    * 自有仓储库存查看列表
    *
    */
    function index(){

        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
    	   if ($branch_ids){
            $base_filter['branch_id'] = $branch_ids;
        }else{
            $base_filter['branch_id'] = 'false';
        }
        $actions = array(
            array(
                'label' => '批量设置安全库存',
                'href'=>'index.php?app=wms&ctl=admin_stock&act=batch_safe_store',
                'target' => "dialog::{width:700,height:400,title:'批量设置安全库存'}",
            ),

        );
        $this->finder('wms_mdl_products',array(
            'title'=>'总库存列表',
            'base_filter' => $base_filter,
            'actions' => $actions,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>true,
            //'actions'=>$actions,
            'use_buildin_filter'=>true,

            'object_method'=>array('count'=>'countlist','getlist'=>'getlist')

        ));

    }


     /**
     * 库存查询相关方法，2011.11.01更新
     */
    function search(){
        if($_POST['stock_search']){
            $keywords = addslashes(trim($_POST['stock_search']));
            $stockObj = kernel::single('wms_stock');
            $data = $stockObj->search_stockinfo($keywords);
            $str = '<em style="color:red">'.$keywords.'</em>';
            foreach ($data as &$row) {
                $row['bn']      = str_replace($keywords,$str,$row['bn']);
                $row['barcode'] = str_replace($keywords,$str,$row['barcode']);
                $row['name']    = str_replace($keywords,$str,$row['name']);
            }
            $this->pagedata['data'] = $data;
            $this->pagedata['keywords'] = $keywords;
        }
        $this->page("admin/stock/search.html");
    }

    /**
     * 批量设置安全库存
     */
    public function batch_safe_store() {

        //批量设置任务
        if($_POST) {
            $this -> batch_safe_store_set();
        }

        $suObj = &app::get('purchase')->model('supplier');
        $data  = $suObj->getList('supplier_id, name','',0,-1);
        $branchObj = kernel::single('wms_branch');
        // 获取操作员管辖仓库
        $is_super = kernel::single('desktop_user')->is_super();
        $selfbranch_id = $branchObj->getBranchwmsByUser($is_super);
        $brObj = &app::get('ome')->model('branch');
        $row   = $brObj->getList('branch_id, name',array('branch_id'=>$selfbranch_id),0,-1);
        $this->pagedata['branch_list']   = $branch_list;
        $is_super = 1;
        $this->pagedata['is_super']   = $is_super;

        $this->pagedata['supplier'] = $data;
        $operator = kernel::single('desktop_user')->get_name();
        $this->pagedata['operator'] = $operator;

        $this->pagedata['branch']   = $row;
        $this->pagedata['branchid']   = $branch_id;
        $this->pagedata['sel_branch_id']   = intval($_GET['branch_id']);
        $this->pagedata['cur_date'] = date('Ymd',time()).$order_label;
        $this->pagedata['io'] = $io;

        $this->display("admin/stock/batch_safe_store.html");
    }

    /**
    * 批量安全库存设置保存
    *
    */
    public function batch_safe_store_set(){
            $page_no = intval($_POST['page_no']); // 分页处理
            $page_size = 10;
            $filter['branch_id'] = intval($_POST['branch']);//仓库
            //$filter['is_locked'] = '0';//跳过已经锁定的商品
            $filter['filter_sql'] = "( is_locked is null or is_locked = '0')";//修复当是否锁定字段为null的部分信息更新不到的问题
            $init_all = intval($_POST['init_all']);
            $init_type = intval($_POST['init_type']);//1固定数量，2按销量计算
            $safe_store = intval($_POST['safe_store']);
            $supply_type = intval($_POST['supply_type']);//1固定订货周期 　　 2供应商补货
            $last_modified = time();
            if($init_all != 1) $filter['safe_store'] = 0;
            $oBranchPorduct = &app::get('ome')->model('branch_product');

            if($init_type == 1)://固定数量设置
            $result = $oBranchPorduct -> update(array('safe_store'=>$safe_store,'last_modified'=>$last_modified),$filter);
                $this -> batch_upd_products();
                echo('finish');
                die();

            elseif($init_type == 2)://按销量计算
                $days = intval($_POST['days']);
                $hour = intval($_POST['hour']);

                //所有供应商的到货天数
                if ($supply_type == 2) {
                    $oSupplier = &app::get('purchase')->model('supplier');
                    $suppliers = $oSupplier -> getList('supplier_id,arrive_days');
                    foreach($suppliers as $v){
                        $this -> suppliers[$v['supplier_id']] = $v['arrive_days'];
                    }
                }

                $branch_products = $oBranchPorduct -> getList('product_id',$filter,$page_no*$page_size,$page_size);
                if (!$branch_products) {
                    $this -> batch_upd_products();
                    echo('finish');
                    die();
                }else{
                    if ($page_no == 0){
                        $total_products = $oBranchPorduct -> count($filter);
                        echo(ceil($total_products/$page_size));
                    }
                }
                for($i=0;$i<sizeof($branch_products);$i++) {
                    $safe_store = $this -> calc_safe_store($branch_products[$i]['product_id'],$days,$hour,$filter['branch_id'],$supply_type);
                    $filter['product_id'] = $branch_products[$i]['product_id'];
                    $oBranchPorduct -> update(array('safe_store'=>$safe_store,'last_modified'=>$last_modified),$filter);
                }
        else:
            echo('Fatal error:init_type is null');
            endif;

            die();
            // echo "<script>$$('.dialog').getLast().retrieve('instance').close();</script>";
        }


    /**
     * 批量更新标志位，增加库存告警颜色提示
     */
    public function batch_upd_products() {
        $branchObj = kernel::single('wms_branch');
        // 获取操作员管辖仓库
        $is_super = kernel::single('desktop_user')->is_super();
        $selfbranch_id = $branchObj->getBranchwmsByUser($is_super);
        $sql = 'UPDATE sdb_ome_products SET alert_store=0';
        kernel::database()->exec($sql);

        $sql = 'UPDATE sdb_ome_products SET alert_store=999 WHERE product_id IN
            (
                SELECT product_id FROM sdb_ome_branch_product
                WHERE safe_store>(store - store_freeze + arrive_store) AND branch_id in ('.implode(',',$selfbranch_id).')
            )
        ';
        kernel::database()->exec($sql);
    }

    /**
     * 计算商品的日平均销量
     * @param int $product_id 商品ID
     * @param int $days 天数,1-30
     * @param int $hour 时间点,0-23
     */
    public function calc_product_vol($product_id,$days,$hour,$branch_id){
        $end_time = strtotime(date('Y-m-d '.$hour.':00:00'));
        if(date('H')<$hour) {
            $end_time = strtotime('-1 days',$end_time);
        }
        $start_time = strtotime('-'.$days.' days',$end_time);
        /**
         * sdb_ome_iostock type_id
         * 3	销售出库
         * 100	赠品出库
         * 300	样品出库
         * 7	直接出库
         * 6	盘亏
         * 5	残损出库
         */
        $oIostock = &app::get('ome')->model('iostock');
        $sql = 'SELECT sum(nums) as total FROM sdb_ome_iostock AS A
                    LEFT JOIN sdb_ome_delivery_items_detail AS B ON A.original_item_id = B.item_detail_id
                    WHERE A.type_id=3
                    AND A.branch_id='.$branch_id.'
                    AND B.product_id='.$product_id.'
                    AND A.create_time>='.$start_time.'
                    AND A.create_time<='.$end_time.' ';
        $sale_volumes = $oIostock -> db -> select($sql);
        $sale_volumes = ceil($sale_volumes[0]['total']/$days);
        return $sale_volumes;
    }

    /**
     * 计算商品的安全库存数
     * @param int $product_id 商品ID
     * @param int $days 天数,1-30
     * @param int $hour 时间点,0-23
     */
    public function calc_safe_store($product_id,$days,$hour,$branch_id,$supply_type){

        //获取该商品对应的供应商
        $oProducts = &app::get('ome')->model('products');
        $goods_id = $oProducts -> getList('goods_id',array('product_id'=>$product_id));
        $goods_id = $goods_id[0]['goods_id'];

        $oSupplierGoods = &app::get('purchase')->model('supplier_goods');
        $supplier_id = $oSupplierGoods -> getList('supplier_id',array('goods_id'=>$goods_id));
        $supplier_id = $supplier_id[0]['supplier_id'];

        //供应商对应的到货天数
        if ($supply_type == 2) {
            $arrive_days = $this -> suppliers[$supplier_id];
        }else{
            $arrive_days = $days;
        }

        //最近几天的日平均销量
        $sale_volumes = $this -> calc_product_vol($product_id,$days,$hour,$branch_id);

        //返回安全库存数
        $safe_store = 0;
        if($arrive_days) {
            $safe_store = $sale_volumes * $arrive_days;
        }
        return $safe_store;
    }

}
?>
