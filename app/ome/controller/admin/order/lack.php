<?php

/**
 * 缺货列表
 */
class ome_ctl_admin_order_lack extends desktop_controller {

    var $workground = "order_center";

    /**
     * 缺货搜索
     *
     * @param void
     * @return void
     */
    function index() {

        $params = array(
            'title'=>'缺货列表',
            'actions' => array(),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,

        );
        $query_data = $_POST;
        unset($query_data['act'],$query_data['ctl'],$query_data['app']);
        $params['actions'][] = array(
            'label'=>app::get('ome')->_('导出'),
            'class'=>'export',
            'icon'=>'add.gif',
            'submit'=>'index.php?app=ome&ctl=admin_order_lack&act=export&'.http_build_query($query_data),
            'target'=>'dialog::{width:400,height:170,title:\'导出\'}'
        );
        $is_export_purchase = kernel::single('desktop_user')->has_permission('order_lack_purchase');#增加商品导出权限
        if ($is_export_purchase) {
            $params['actions'][]= array( 
                'label' => '生成采购单',
                'submit' => 'index.php?app=ome&ctl=admin_order_lack&act=createPurchase&'.http_build_query($query_data),
                'target' => '_blank'
                        
            );
              
        }
        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('orderlack_finder_top');
            $panel->setTmpl('admin/finder/finder_lackpanel_filter.html');
            $panel->show('ome_mdl_order_lack', $params);
        }
        $this->finder('ome_mdl_order_lack',$params);
    }

    
    /**
     * 列表搜索.
     * @
     * @
     * @access  public
     * @author cyyr24@sina.cn
     */
    function search()
    {
        $oBranch = &$this->app->model('branch');
        $branch_list = $oBranch->getOnlineBranchs('branch_id,name');
        $this->pagedata['branch_list'] = $branch_list;
        unset($branch_list);
        $oShop = &$this->app->model('shop');
        $shop_list = $oShop->getlist('shop_id,name',0,-1);
        $this->pagedata['shop_list'] = $shop_list;
        unset($shop_list);
        $this->page('admin/order/lack_search.html');
    }

    
    /**
     * 查看商品冻结列表
     * @param   product_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function show_store_freeze_list($product_id)
    {
        
        $oOrder_lack = $this->app->model('order_lack');
        $order_lack = $oOrder_lack->get_stocklist($product_id);
        $this->pagedata['order_lack'] = $order_lack;
        unset($order_lack);
        $this->singlepage('admin/order/lack_list.html');
    }

    
    /**
     * 订单冻结列表
     * @param   int product_id
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function show_order_freeze_list($product_id,$bn)
    {
        $oOrder_lack = $this->app->model('order_lack');

        $count  = count($oOrder_lack->get_order($product_id,$bn));
        $page = $_GET['page'] ? $_GET['page'] : 1;
        $pagelimit = 10;
        $offset = ($page-1)*$pagelimit;
        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>'index.php?app=ome&ctl=admin_order_lack&act=show_order_freeze_list&p[0]='.$product_id.'&p[1]='.$bn.'&target=container&page=%d',
        ));
        $order_lack = $oOrder_lack->get_orderlist($product_id,$bn,$pagelimit,$offset);
        $this->pagedata['order_lack'] = $order_lack;
        $this->pagedata['pager'] = $pager;
        unset($order_lack);
        if($_GET['target']){
            return $this->display('admin/order/orderlack_list.html');
        }
        $this->singlepage('admin/order/orderlack_list.html');
    }

    
    /**
     * 显示在途库存.
     * @param   product_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function show_arrive_store($product_id)
    {
        $oOrder_lack = $this->app->model('order_lack');
        $count  = $oOrder_lack->getArrivestore($product_id);
        $page = $_GET['page'] ? $_GET['page'] : 1;
        $pagelimit = 10;
        $offset = ($page-1)*$pagelimit;
        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$page,
            'total'=>$total_page,
            'link'=>'index.php?app=ome&ctl=admin_order_lack&act=show_arrive_store&p[0]='.$product_id.'&target=container&page=%d',
        ));
        $order_lack = $oOrder_lack->getArrivestorelist($product_id,$pagelimit,$offset);
        $this->pagedata['order_lack'] = $order_lack;
        $this->pagedata['pager'] = $pager;
        unset($order_lack);
         if($_GET['target']){
            return $this->display('admin/order/arrivestore_list.html');
        }
        $this->singlepage('admin/order/arrivestore_list.html');
    }

    
    /**
     * 生成采购单
     * @param  product_id
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function createPurchase()
    {
        $filter = array();
         // 商品查询参数
        if($_POST['isSelectedAll']=='_ALL_') {
            $product_ids = &app::get('ome')->model('supply_product')->getList('*',$_POST,0,-1);
            for($i=0;$i<sizeof($product_ids);$i++){
                $product_id[] = $product_ids[$i]['product_id'];
            }
        }else{
            $product_id = $_POST['product_id'];
        }

        $this->pagedata['product_ids'] = implode(',',$product_id);
        // 获取供应商id
        $sql = 'SELECT supplier_id FROM sdb_purchase_supplier_goods AS a
                LEFT JOIN sdb_ome_products AS b ON a.goods_id=b.goods_id
                WHERE b.product_id IN ('.implode(',',$product_id).')
                LIMIT 1';
        
        $rs = kernel::database()->select($sql);
        if($rs) $supplier_id = $rs[0]['supplier_id'];
    
        $filter = $_GET;
        unset($filter['act'],$filter['ctl'],$filter['app']);
        $this->pagedata['filter'] = http_build_query($filter);
        $suObj = &app::get('purchase')->model('supplier');
        $data = $suObj->getList('supplier_id, name','',0,-1);

        $brObj = &app::get('ome')->model('branch');
        $row = $brObj->getList('branch_id, name','',0,-1);

        /*
         * 获取操作员管辖仓库
         */
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
           $branch_list = $brObj->getBranchByUser();
        }
        $this->pagedata['branch_list'] = $branch_list;
        $is_super = 1;
        $this->pagedata['is_super'] = $is_super;

        //获取设置的采购方式
        $po_type = &app::get('ome')->getConf('purchase.po_type');
        if (!$po_type) $po_type = 'credit';
        $this->pagedata['po_type'] = $po_type;
        

        $supplier = $suObj->dump($supplier_id, 'supplier_id,name,arrive_days');

        


        $operator = kernel::single('desktop_user')->get_name();
        $this->pagedata['operator'] = $operator;
        $this->pagedata['supplier'] = $supplier;
        $this->pagedata['branchid'] = $branch_id;
        $this->pagedata['branch'] = $row;
        $this->pagedata['cur_date'] = date('Ymd',time()).'采购单';

        $this->singlepage("admin/order/lack/purchase_create.html");
    }

    
    /**
     * 获取需采购货品
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getSafeStock($product_ids,$supplier_id)
    {
        if ($product_ids) {
            $filter['product_id'] = explode(',',$product_ids);
        }
        $filter_data = $_GET;
        unset($filter_data['act'],$filter_data['ctl'],$filter_data['app'],$filter_data['p']);
        $filter = array_merge($filter,$filter_data);
        $oOrder_lack = $this->app->model('order_lack');
        $oPo = &app::get('purchase')->model('po');
        $pObj = &app::get('ome')->model('products');
        $data = $oOrder_lack->getlist('*',$filter);
        $lack_data = array();
        foreach ($data as $k=>$v ) {
            if ($v['product_id']>0) {
                $v['num'] = $v['product_lack'];
                if($supplier_id > 0){
                    $v['price'] = $oPo->getPurchsePriceBySupplierId($supplier_id, $v['product_id'], 'desc');
                    if (!$v['price']){
                        $v['price'] = 0;
                    }
                }else{
                    $product = $pObj->dump(array('product_id'=>$v['product_id']),'cost');
                    $v['price'] = $product['price']['cost']['price'];
                }
                $lack_data[] = $v;
            }
        }
        echo json_encode($lack_data);
    }

    
    /**
     * 供应商.
     * @param 
     * @return
     * @access  public
     * @author cyyr24@sina.cn
     */
    function supplier()
    {
        
    }

    /**
     * 缺货商品导出
     * @param  array
     * @return 
     * @access  public
     * @author cyyr24@sina.cn
     */
    function export()
    {
        $filter = $_GET;
        unset($filter['act'],$filter['ctl'],$filter['app']);
        $this->pagedata['filter'] = $filter;
        if( !$this->pagedata['thisUrl'] )
            $this->pagedata['thisUrl'] = $this->url;
        $ioType = array();
        foreach( kernel::servicelist('desktop_io') as $aio ){
            $ioType[] = $aio->io_type_name;
        }
        $this->pagedata['ioType'] = $ioType;
        echo $_GET['change_type'];
        if( $_GET['change_type'] )
            $this->pagedata['change_type'] = $_GET['change_type'];
        echo $this->fetch('admin/order/lack/export.html');
    }
}