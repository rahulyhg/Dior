<?php
/**
 * 商品明细controller
 *
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_ctl_shop_frame extends desktop_controller {
    
    var $workground = 'goods_manager';
    var $defaultWorkground = 'goods_manager';

    function __construct($app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
    }

    /**
     * 商品明细列表
     *
     * @return void
     * @author
     **/
    public function index()
    {
        $base_filter = array();
        
        if($_POST['shop_id']) {
            $_SESSION['shop_id'] = $_POST['shop_id'];
        } elseif($_GET['filter']['shop_id']) {
            $_SESSION['shop_id'] = $_GET['filter']['shop_id'];
        }
        $base_filter['shop_id'] = $_SESSION['shop_id'];
        
        $shop = $this->app->model('shop')->getList('name', array('shop_id'=>$_SESSION['shop_id']));
        $title = "<span style='color:red;'>".$shop[0]['name']."</span>上下架管理";
        $_SESSION['shop_name'] = addslashes($shop[0]['name']);
        $view = 0;
        if ($_GET['view'] == '1') {
            unset($params['actions'][1]);
            $view = 1;
        }
        
        if ($_GET['view'] == '2') {
            unset($params['actions'][2]);
            $view = 2;
        }

        $params = array(
            'title' => $title,
            'actions' => array(
                1 => array('label' => $this->app->_('批量上架'),'target' => 'dialog::{title:\'批量上架\'}','submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=approveQueueNotice&p[0]=onsale'),
                2 => array('label' => $this->app->_('批量下架'),'target' => 'dialog::{title:\'批量下架\'}','submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=approveQueueNotice&p[0]=instock'),
                # 3 => array('label' => $this->app->_('开启自动上下架'),'submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=set_frame&p[0]=true','target'=>'refresh'),
                # 4 => array('label' => $this->app->_('关闭自动上下架'),'submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=set_frame&p[0]=false','target'=>'refresh'),
                //5 => array('label' => $this->app->_('导出'),'target'=>'dialog::{width:400,title:\'导出店铺【'.addslashes($shop[0]['name']).'】商品\'}','href'=>'index.php?app=inventorydepth&ctl=shop_frame&act=exportDialog&p[0]='.$_SESSION['shop_id']),
                 5=> array('label'=>'导出','submit'=>'index.php?app=inventorydepth&ctl=shop_frame&act=exportDialog&p[0]='.$_SESSION['shop_id'].'&p[1]='.$view,'target'=>'dialog::{width:400,height:200,title:\'导出\'}'),),
            'use_buildin_export' => false,
            'use_buildin_recycle' => false,
            'use_buildin_filter' => true,
            'base_filter' => $base_filter,
            'object_method' => array(
                'count'=>'count',
                'getlist'=>'getFinderList',
            ),
        );
        
        $shop = $this->app->model('shop')->dump(array('shop_id'=>$_SESSION['shop_id']));
        if ($shop['business_type']=='fx') {
            unset($params['actions'][1],$params['actions'][2]);
        }


        $is_export = kernel::single('desktop_user')->has_permission('inventorydepth_shop_export');#增加店铺商品导出
        if(!$is_export){
            unset($params['actions'][5]);
        }
        
        $this->finder('inventorydepth_mdl_shop_frame',$params);
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function exportDialog($shop_id,$view) 
    {
        $filter = $_POST;
        if($view == 1){
            $filter['approve_status'] = 'onsale';
        }elseif($view == 2){
            $filter['approve_status'] = 'instock';
        }

        $isSelectedAll = $this->_request->get_post('isSelectedAll');
        $_ids = $this->_request->get_post('id');
        if($isSelectedAll != '_ALL_' && $_ids){
            $ids = $_ids;
        }else{
            $ids = array();
        }
        if(!empty($ids)){
            $this->pagedata['ids'] = $ids;
        }
        if(!empty($filter)){
            $this->pagedata['filter'] = $filter;
        }
        $this->pagedata['shop_id'] = $shop_id;

        $this->display('shop/items/export.html');
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function _views()
    {
        $views = array(
            0 => array('label' => $this->app->_('全部'),'addon'=>'','href'=>'','filter'=>array()),
            1 => array('label' => $this->app->_('在架数'),'addon'=>'','href'=>'','filter'=>array('approve_status'=>'onsale','range'=>'all')),
            2 => array('label' => $this->app->_('下架数'),'addon'=>'','href'=>'','filter'=>array('approve_status'=>'instock','range'=>'all')),
        );
        $itemsModel = $this->app->model('shop_items');
        foreach ($views as $key => $view) {
            $view['filter']['shop_id'] = $_SESSION['shop_id'];
            $views[$key]['addon'] = $itemsModel->count($view['filter']);
            $views[$key]['href'] = 'index.php?app=inventorydepth&ctl=shop_frame&act=index&view='.$key;
        }
        return $views;
    }

    /**
     * 上下架设置
     *
     * @return void
     * @author 
     **/
    public function set_frame($config = 'true',$id = null)
    {
        if($id) $_POST['id'][] = $id;
		
        //shop_id
		if(empty($_POST['id'])){
           $_POST['shop_id']    = $_SESSION['shop_id'];
        }
        
        $this->begin();
        if ($_POST) {
            $result = $this->app->model('shop_items')->update(array('frame_set'=>$config),$_POST);
        } else {
            $result = false;
        }

        $msg = $result ? $this->app->_('设置成功!') : $this->app->_('设置失败');
        $this->end($result,$msg,'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh.delay(400,finderGroup["'.$_GET['finder_id'].'"]);');
    }

    /**
     * 上下架加载页
     *
     * @return void
     * @author 
     **/
    public function approve_loading($id,$approve_status)
    {
        $this->pagedata['id'] = $id;
        $this->pagedata['approve_status'] = $approve_status;
        if ($approve_status == 'onsale') {
            $this->pagedata['request_words'] = $this->app->_('正在上架，请耐心等待...');
        }elseif ($approve_status == 'instock') {
            $this->pagedata['request_words'] = $this->app->_('正在下架，请耐心等待...');
        }

        $this->page('shop/items/loading.html');
    }

    /**
     * 单个上下架
     *
     * @return void
     * @author 
     **/
    public function singleApprove()
    {
        $this->begin();
        $approve_status = $_POST['approve_status']; 
        $id             = $_POST['id'];
        if (!$approve_status || !$id) {
            $this->end(false,$this->app->_('参数错误!'));
        }

        $itemModel = $this->app->model('shop_items');
        $item = $itemModel->getList('id,title,approve_status,shop_id,shop_bn,iid,frame_set',array('id'=>$id),0,1);
        if (!$item) {
            $this->end(false,$this->app->_('商品不存在!'));
        }
        /*
        if ($item[0]['frame_set'] == 'false') {
            $this->end(false,$this->app->_('上下架设置未开启!'));
        }*/
        /*
        $m = $itemModel->schema['columns']['approve_status']['type'][$approve_status];
        if ($item[0]['approve_status'] == $approve_status) {
            $this->end(false,$m.'失败：'.$item[0]['title'].'已经'.$m);
        }*/

        $approve = array(
            'approve_status' => $approve_status,
            'iid' => $item[0]['iid'],
            'title' => $item[0]['title'],
            'id' => $item[0]['id'],
        );

        $result = kernel::single('inventorydepth_shop')->doApproveSync($approve,$item[0]['shop_id'],$item[0]['shop_bn'],$msg);

        $this->end($result,$msg);
    }

    /**
     * 批量上下架，放队列
     *
     * @return void
     * @author 
     **/
    public function approveQueueNotice($approve_status)
    {
        $this->pagedata['post'] = http_build_query($_POST);
        $this->pagedata['approve_status'] = $approve_status;
        $this->page('shop/items/approve_in_queue.html');
    }

    /**
     * 上下架调整
     *
     * @return void
     * @author 
     **/
    public function goToFrame()
    {

        $shops = $this->app->model('shop')->getList('shop_id,shop_bn,name');

        $this->pagedata['shops'] = $shops;

        $this->page('shop/items/frameIndex.html');
    }

    /**
     * @description 获取商品ID
     * @access public
     * @param String $id 商品ID
     * @return void
     */
    public function getApplyRegu() 
    {        
        $iids = $_POST['iid'];$shop_id = $_POST['shop_id']; $shop_bn = $_POST['shop_bn'];
        if( !$iids || !$shop_id || !$shop_bn) {
            $result = array('status'=>'fail','msg'=>'参数为空!');
            echo json_encode($result);exit;
        }

        $skus = $this->app->model('shop_adjustment')->getList('shop_product_bn,shop_stock,release_stock,shop_iid',array('shop_iid'=>$iids,'shop_id'=>$shop_id));
        if(!$skus){ 
            $result = array('status'=>'fail','msg'=>'无对应SKU!');
            echo json_encode($result);exit;
        }
        $items = array();
        foreach ($skus as $sku) {
            $pbns[] = $sku['shop_product_bn'];
            $items[$sku['shop_iid']] = array(
                'iid' => $sku['shop_iid'],
                'shop_id' => $shop_id,
                'shop_bn' => $shop_bn,
                'id' => md5($shop_id.$sku['shop_iid']),
            );
            $items[$sku['shop_iid']]['skus'][] = $sku;
        }
        
        $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$pbns));
        if(!$products){ 
            $result = array('status'=>'fail','msg'=>'无关联货号!');
            echo json_encode($result);exit;
        }

        # 捆绑商品写内存
        kernel::single('inventorydepth_stock_pkg')->writeMemory($products);
        kernel::single('inventorydepth_stock_products')->writeMemory($products);

        $data = array();
        foreach ($items as $item) {
            $rr = kernel::single('inventorydepth_logic_frame')->getExecRegu($item);
            if ($rr) {
            $html = <<<EOF
        <a href="index.php?app=inventorydepth&ctl=regulation&act=dialogEdit&p[0]={$rr['regulation_id']}&finder_id={$_GET['_finder']['finder_id']}&regulation_readonly=true" target="dialog::{title:'修改规则'}">{$rr['heading']}</a>
EOF;
            } else {
                $html = '-';
            }
            $data[] = array(
                'id' => $item['id'],    
                'html' => $html,
                'iid' => $item['iid'],
            );
        }
        $result = array('status'=>'succ','data'=>$data);
        echo json_encode($result);exit;
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function getApproveStatus() 
    {
        $iids = $_POST['iid'];$shop_id = $_POST['shop_id']; $shop_bn = $_POST['shop_bn']; $finder_id = $_POST['finder_id'];$shop_type = $_POST['shop_type'];
        if( !$iids || !$shop_id || !$shop_bn) {
            $result = array('status'=>'fail','msg'=>'参数为空!');
            echo json_encode($result);exit;
        }
        $shop = $this->app->model('shop')->dump(array('shop_id'=>$shop_id));
        $shopfactory = inventorydepth_service_shop_factory::createFactory($shop['shop_type'],$shop['business_type']);
        if ($shopfactory === false) {
            $result = array('status'=>'fail','msg'=>'店铺类型有误！');
            echo json_encode($result);exit;
        }
        $result = $shopfactory->downloadByIIds($iids,$shop_id,$errormsg);

        if (empty($result)) {
            $result = array('status'=>'fail','msg'=>$errormsg);
            echo json_encode($result);exit;
        }

        # save in memory
        $shop_product_bn = array();
        foreach($result as $r){
            if ($r['skus']['sku']) {
                foreach ($r['skus']['sku'] as $sku) {
                    $shop_product_bn[] = $sku['outer_id'];
                }
            } else {
                $shop_product_bn[] = $r['outer_id'];
            }
        }
        $shop_product_bn = array_filter($shop_product_bn);
        $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$shop_product_bn));
        $products = $products ? $products : array();
        kernel::single('inventorydepth_stock_pkg')->writeMemory($products);
        kernel::single('inventorydepth_stock_products')->writeMemory($products);

        $skuMapping = array();
        $list = $this->app->model('shop_adjustment')->getList('shop_product_bn,bind',array('shop_product_bn'=>$shop_product_bn,'mapping'=>'1','shop_id'=>$shop_id));
        foreach ($list as $key => $value) {
            $skuMapping[$value['shop_product_bn']] = $value['bind'];
        }

        unset($shop_product_bn,$products);

        foreach ($result as $r) {
            $iid = $r['iid'] ? $r['iid'] : $r['num_iid'];
            $id = md5($shop_id.$iid);
            if ($r['approve_status'] == 'onsale') {
                $color = 'green';
                $word = $this->app->_('在售');
                $href = "index.php?app=inventorydepth&ctl=shop_items&act=approve_loading&p[0]={$id}&p[1]=instock";
                $title = "正在为【{$r['title']}】下架";
                $t = "点击下架【{$r['title']}】";
                $confirm_notice = "确定下架【{$r['title']}】";
            }else{
                $color = '#a7a7a7';
                $word = $this->app->_('下架');
                $href = "index.php?app=inventorydepth&ctl=shop_items&act=approve_loading&p[0]={$id}&p[1]=onsale";
                $title = "正在为【{$r['title']}】上架";
                $t = "点击上架【{$r['title']}】";
                $confirm_notice = "确定上架【{$r['title']}】";
            }

            $html = <<<EOF
            <a style="background-color:{$color};float:left;text-decoration:none;" href="javascript:void(0);" title="{$t}" onclick="if(confirm('{$confirm_notice}')){new Event(event).stop();new Dialog('{$href}',{title:'{$title}',onClose:function(){
                    var data = ['{$iid}'];
                    new Request.JSON({
                        url:'index.php?app=inventorydepth&ctl=shop_frame&act=getApproveStatus',
                        method:'post',
                        data:{'iid':data,'shop_id':'{$shop_id}','shop_bn':'{$shop_bn}','finder_id':'{$finder_id}','shop_type':'{$shop_type}'},
                        onComplete:function(rsp){
                            if(rsp.status=='fail'){console.log(rsp.msg);return;}
                            if(rsp.status=='succ'){
                                rsp.data.each(function(item,index){
                                    var id = 'item-approve-'+item.id;
                                    \$(id).setHTML(item.html);
                                });
                            }
                        }
                    }).send();

            } });}"><span style="color:#eeeeee;padding:2px;">&nbsp;{$word}&nbsp;</span></a>
EOF;
            
            $shop_product_bn = array(); $pkgFlag = $productFlag = array();
            if ($r['skus']['sku']) {
                foreach ($r['skus']['sku'] as $sku) {
                    $shop_product_bn[] = $sku['outer_id'];
                    if (isset($skuMapping[$sku['outer_id']])) {
                        $productFlag[] = $sku['outer_id'];
                    }
                    if ($skuMapping[$sku['outer_id']]) {
                        $pkgFlag[] = $sku['outer_id'];
                    }
                }
            } else {
                $shop_product_bn[] = $r['outer_id'];
                if (isset($skuMapping[$r['outer_id']])) {
                    $productFlag[] = $r['outer_id'];
                }
                if ($skuMapping[$r['outer_id']]) {
                    $pkgFlag[] = $r['outer_id'];
                }
            }
            $shop_product_bn = array_filter($shop_product_bn);
            if ( $shop_product_bn ) {
                # $skuMapping = $this->app->model('shop_adjustment')->getList('shop_product_bn,bind',array('shop_product_bn'=>$shop_product_bn,'mapping'=>'1','shop_id'=>$shop_id),0,1);
                if ($pkgFlag) {
                    $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($pkgFlag[0],$shop_bn,$shop_id);
                    $actual_stock = ($actual_stock===false) ? '-' : $actual_stock;
                } elseif ($productFlag){
                    $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_goods_actual_stock($shop_product_bn,$shop_bn,$shop_id);
                    $actual_stock = ($actual_stock===false) ? '-' : $actual_stock;
                } else {
                    $actual_stock = '-';
                }
                
            } else {
                $actual_stock = '-';
            }
            

            $items[] = array(
                'iid' => $iid,
                'approve_status' => $r['approve_status'],
                'html' => $html,
                'id' => $id,
                'num' => $r['num']==='' ? '-' : $r['num'],
                'actual_stock' => $actual_stock,
            );
        }
        
        $data = array('status'=>'succ','data'=>$items);
        echo json_encode($data);exit;
    }

}
