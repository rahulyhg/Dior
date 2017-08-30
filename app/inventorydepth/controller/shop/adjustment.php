<?php
/**
 * 店铺商品信息调整controller
 *
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_ctl_shop_adjustment extends desktop_controller {

    var $workground = 'goods_manager';
    var $defaultWorkground = 'goods_manager';

    function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * 调整 列表
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
        $title = "<span style='color:red;'>".$shop[0]['name']."</span>在售库存管理";

        $params = array(
            'title' => $title,
            'actions' => array(
                0 => array('label'=>$this->app->_('批量开启回写库存'),'submit'=>'index.php?app=inventorydepth&ctl=shop_skus&act=set_request&p[0]=true','target'=>'refresh'),
                1 => array('label'=>$this->app->_('批量关闭回写库存'),'submit'=>'index.php?app=inventorydepth&ctl=shop_skus&act=set_request&p[0]=false','target'=>'refresh'),
                2 => array('label'=>$this->app->_('发布库存'),'submit'=>'index.php?app=inventorydepth&ctl=shop_adjustment&act=releasePage','target'=>'dialog::{title:\'批量发布\'}'),
                3 => array('label'=>$this->app->_('导出发布库存模板'),'submit'=>'index.php?app=inventorydepth&ctl=shop_adjustment&act=index&action=export','target' => 'dialog::{width:400,height:170,title:\'导出发布库存模板\'}'),
                4 => array('label'=>$this->app->_('导入发布库存'),'href'=>'index.php?app=inventorydepth&ctl=shop_adjustment&act=index&action=import','target' =>  'dialog::{width:400,height:150,title:\'导入发布库存\'}'),

            ),
            'use_buildin_recycle' => false,
            'use_buildin_filter' => true,
            'base_filter' => $base_filter,
            'object_method' => array(
                'count'=>'count',
                'getlist'=>'getFinderList',
            ),
        );

        $this->pagedata['benchobj']    = kernel::single('inventorydepth_stock')->get_benchmark();
        $this->pagedata['calculation'] = kernel::single('inventorydepth_math')->get_calculation();
        $this->pagedata['res_full_url'] = $this->app->res_full_url;

        $this->finder('inventorydepth_mdl_shop_adjustment',$params);
    }

    /**
     * 列表TAB页
     *
     * @return void
     * @author
     **/
    public function _views()
    {
        $views = array(
            0 => array('label'=>$this->app->_('全部'),'addon'=>'','href'=>'','filter'=>''),
            1 => array('label'=>$this->app->_('已关联'),'addon'=>'','href'=>'','filter'=>array('mapping'=>1)),
            2 => array('label'=>$this->app->_('未关联'),'addon'=>'','href'=>'','filter'=>array('mapping'=>0,'filter_sql'=>'{table}shop_product_bn is not null AND {table}shop_product_bn != ""','shop_product_bn'=>'exceptrepeat')),
            3 => array('label'=>$this->app->_('货号为空'),'addon'=>'','href'=>'','filter'=>array('filter_sql'=>'({table}shop_product_bn is null OR {table}shop_product_bn="")')),
            4 => array('label'=>$this->app->_('货号重复'),'addon'=>'','href'=>'','filter'=>array('shop_product_bn'=>'repeat')),
        );

        $skusModel = $this->app->model('shop_adjustment');
        foreach ($views as $key=>&$view) {
            $view['filter']['shop_id'] = $_SESSION['shop_id'];
            $view['addon'] = $skusModel->count($view['filter']);
            $view['href'] = 'index.php?app=inventorydepth&ctl=shop_adjustment&act=index&view='.$key;
        }
        return $views;
    }


    /**
     * 保存公式
     *
     * @return void
     * @author
     **/
    public function saveFormula()
    {
        $this->begin();

        if ($_POST['heading'] == ''){
            $this->end(false,$this->app->_('编号或者中文标识不能为空'));
        }

        $formulaModel = $this->app->model('formula');

        $is_exist = $formulaModel->select()->columns('formula_id')
                    ->where('heading=?',$_POST['heading'])
                    ->instance()->fetch_row();
        if ($is_exist) {
            $this->end(false,$this->app->_('公式名已经存在！'));
        }

        $data['style'] = 'stock';
//       $data['bn'] = $_POST['bn'];
        $data['heading'] = $_POST['heading'];
        $data['content'] = array(
//            'benchmark'   => $_POST['benchmark'],
//            'calculation' => $_POST['calculation'],
//            'increment'   => $_POST['increment'],
            'result'      => $_POST['result']
        );
        $data['operator'] = $this->user->get_id();
        $data['operator_ip'] = kernel::single('base_component_request')->get_remote_ip();

        $result = $this->app->model('formula')->insert($data);

        $this->end($result);
    }

    /**
     * 执行公式
     *
     * @return void
     * @author
     **/
    public function execFormula()
    {
        $this->begin();

        if (!$_POST['id']) {
            $this->end(false,$this->app->_('请选择店铺商品'));
        }

        $result = $_POST['result'];unset($_POST['result']);
        if (!$result) {
            $this->end(false,$this->app->_('先填写公式'));
        }

        #   验证公式
        kernel::single('inventorydepth_stock')->formulaRun($result,array(),$errormsg);
        if ($errormsg) {
           $this->end(false,$errormsg);
        }

        # 执行公式
        kernel::single('inventorydepth_stock')->updateReleaseByformula($_POST,$result,$errormsg);

        $msg = $errormsg ? 'javascript:alert("修改失败：'.implode("\n", $errormsg).'");' : '修改成功';
        $result = $errormsg ? false : true;
        $this->end($result,$msg);
    }

    /**
     * 公式列表
     *
     * @return void
     * @author
     **/
    public function listFormula($pageno = 1)
    {
        $pagelimit = 10;

        $formulaModel = $this->app->model('formula');
        $PG['list'] = $formulaModel->getList('*',array('style'=>'stock'),($pageno-1)*$pagelimit,$pagelimit);
        $count = $formulaModel->count(array('style'=>'stock'));

        $total_page = ceil($count/$pagelimit);
        $pager = $this->ui()->pager(array(
            'current'=>$pageno,
            'total'=>$total_page,
            'link'=>'index.php?app=inventorydepth&ctl=shop_adjustment&act=listFormula&p[0]=%d',
        ));

        $this->pagedata = $PG;
        $this->pagedata['pager'] = $pager;
        $this->pagedata['count'] = $count;
        $this->pagedata['pagelimit'] = $pagelimit;
        $this->display('shop/adjustment/stock/list_formula.html');
    }

    /**
     * 删除公式
     *
     * @return void
     * @author
     **/
    public function delFormula()
    {
        $this->begin();
        if (!$_POST) {
            $this->end(false,$this->app->_('请选择公式'));
        }
        $result = $this->app->model('formula')->delete($_POST);
        $this->end($result);
    }

    /**
     * 更新发布库存
     *
     * @return void
     * @author
     **/
    public function update_release_stock()
    {
        $this->begin();
        if (!$_POST['id'] || !isset($_POST['release_stock'])) {
            $this->end(false,$this->app->_('参数错误!'));
        }
        if (!(is_numeric($_POST['release_stock']) && $_POST['release_stock']>=0)) {
            $this->end(false,'发布库存必须是非负数值型！');
        }

        $result = $this->app->model('shop_adjustment')->update(array('release_stock'=>(int)$_POST['release_stock'],'release_status'=>'sleep'),array('id'=>$_POST['id']));

        $this->end($result);
    }

    /**
     * 发布页
     *
     * @return void
     * @author
     **/
    public function releasePage($id = null,$release_stock = null)
    {
        if ($_POST['isSelectedAll'] == '_ALL_') {
            echo '<div style="color:red;font-weight:bold;font-size:30px;">不支持全部货品发布！！！</div>';exit;
        } elseif ( $_POST['id'] ) {
            $way = 'batch';

        }elseif($id){
            $way = 'single';
            $_POST['id'] = $id;

            # 发布库存超出可售库存提示
            $sku = $this->app->model('shop_adjustment')->select()->columns('shop_product_bn,shop_id,shop_bn,release_stock')
                    ->where('id=?',$id)->instance()->fetch_row();

            # 可售库存
            $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_actual_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_id']);

            if ( is_numeric($release_stock) && $release_stock>=0 ) {
                $sku['release_stock'] = $release_stock;
            }

            $_POST['release_stock'] = $sku['release_stock'];

            $this->pagedata['warning'] = ($sku['release_stock'] > $actual_stock) ? true : false;
        }

        if ($_POST) {
            $post = http_build_query($_POST);
            $this->pagedata['post'] = $post;
        }

        # 发布库存覆盖店铺库存
        //$this->app->model('shop_adjustment')->convert_shop_stock($_POST);

        $this->pagedata['way'] = $way;
        $this->display('shop/adjustment/release/show.html');
    }

    /**
     * @description  商品的回写
     * @access public
     * @param void
     * @return void
     */
    public function syncItemStock($iid,$shop_id)
    {
        $stock = array();
        $skus = $this->app->model('shop_adjustment')->getList('shop_product_bn,shop_id,shop_bn,id',array('shop_id'=>$shop_id,'shop_iid'=>$iid));
        $product_bn = array_map('current',$skus);
        $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$product_bn));
        kernel::single('inventorydepth_stock_products')->writeMemory($products);
        unset($products);

        $optLogModel = app::get('inventorydepth')->model('operation_log');
        foreach ($skus as $key=>$sku) {
            $st = kernel::single('inventorydepth_logic_stock')->getStock($sku['shop_product_bn'],$sku['shop_id'],$sku['shop_bn']);
            if ($st === false) { continue; }

            $stock[] = $st;

            $optLogModel->write_log('sku',$sku['id'],'stockup','回写商品中所有货品库存：'.$st['quantity']);
        }

        if ($stock) {
            kernel::single('inventorydepth_shop')->doStockRequest($stock,$shop_id);
        }

        $this->splash('success',null,'成功发出请求！');
    }

    /**
     * 单个发布
     *
     * @return void
     * @author
     **/
    public function singleRelease()
    {
        $this->begin();
        $id = $_POST['id'];

        if (!$id) {
            $this->end(false,$this->app->_('参数错误!'));
        }

        $sku = $this->app->model('shop_adjustment')
                    ->select()->columns('id,shop_id,release_stock,shop_product_bn,mapping,request')
                    ->where('id=?',$id)
                    ->instance()->fetch_row();

        if(!$sku) $this->end(false,$this->app->_('货品不存在!'));

        $memo = array('last_modified'=>time());
        $stocks[$sku['id']] = array(
            'bn' => $sku['shop_product_bn'],
            'quantity' => (is_numeric($_POST['release_stock']) && $_POST['release_stock']>=0) ? $_POST['release_stock'] : $sku['release_stock'],
            'memo' => json_encode($memo),
        );

        kernel::single('inventorydepth_shop')->doStockRequest($stocks,$sku['shop_id'],true);

        // 记录操作日志
        $optLogModel = app::get('inventorydepth')->model('operation_log');
        $optLogModel->write_log('sku',$id,'stockup','单个发布库存：'.$stocks[$sku['id']]['quantity']);

        $this->end(true,$this->app->_('发布中'));
    }

    /**
     * 批量发布
     *
     * @return void
     * @author
     **/
    public function batchRelease()
    {
        $this->begin();

        if (!$_POST) {
            $this->end(false,$this->app->_('请选择店铺商品!'));
        }

        $adjustmentModel = $this->app->model('shop_adjustment');

        /*
        $_POST['request'] = 'false';
        $row = $adjustmentModel->getList('id',$_POST,0,1);
        if ($row) {
            $this->end(false,$this->app->_('存在不允许回写的货品!'));
        }
        unset($_POST['request']);*/

        $_POST['mapping'] = '0';
        $row = $adjustmentModel->getList('id',$_POST,0,1);
        if ($row) {
            $this->end(false,$this->app->_('存在未匹配的货品!'));
        }
        unset($_POST['mapping']);

        $adjustmentModel->appendCols = '';
        $shops = $adjustmentModel->getList('distinct shop_id,shop_name',$_POST);

        foreach ($shops as $key => $shop) {
            $offset = 0; $limit = 50; $_POST['shop_id'] = $shop['shop_id'];

            $params = $_POST; $params['limit'] = $limit;

            // 操作员信息
            $params['operInfo'] = kernel::single('inventorydepth_func')->getDesktopUser();

            $count = $adjustmentModel->count($_POST);
            
            if($count<=0) continue;
            $title = "批量店铺【{$shop['shop_name']}】库存回写";

            $total = floor($count/$limit);
            for ($i=$total; $i>=0 ; $i--) {
                $params['offset'] = $i*$limit;

                # 插入队列
                kernel::single('inventorydepth_queue')->insert_release_queue($title,$params);
            }
        }

        $this->end(true,$this->app->_('成功插入队列!'));
    }

    /**
     * @description 更新店铺所有货品库存
     * @access public
     * @param void
     * @return void
     */
    public function uploadPage($shop_id)
    {
        if ( !$shop_id ) {
            $this->pagedata['error'] = '请先选择店铺！！！';
        } else {
            $shop = $this->app->model('shop')->select()->columns('*')->where('shop_id=?',$shop_id)->instance()->fetch_row();
            if ( !$shop ) {
                $this->pagedata['error'] = '店铺不存在！！！';
            } elseif (!$shop['node_id']) {
                $this->pagedata['error'] = '店铺【'.$shop['shop_bn'].'】未绑定';
            }
        }

        $this->pagedata['shop'] = $shop;

        $this->display('shop/adjustment/stock/upload.html');
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function batchUpload($shop_id)
    {
        set_time_limit(0);
        $this->begin();

        if ( !$shop_id ) {
            $this->end(false,'请先选择店铺！！！');
        }

        $shop = $this->app->model('shop')->select()->columns('*')->where('shop_id=?',$shop_id)->instance()->fetch_row();
        if ( !$shop ) {
            $this->end(false,'店铺不存在！！！');
        } elseif (!$shop['node_id']) {
            $this->end(false,'店铺【'.$shop['shop_bn'].'】未绑定');
        }

        $count = $this->app->model('products')->count();
        if($count<=0) {
            $this->end(false,'无商品，请先在淘管中添加商品！！！');
        }

        $title = "批量店铺【{$shop['name']}】库存回写";
        $offset = 0; $limit = 50;  $total = floor($count/$limit);
        $params = array('shop_id'=>$shop_id,'limit'=>$limit);
        for ($i=$total; $i>=0 ; $i--) {
            $params['offset'] = $i*$limit;

            # 插入队列
            kernel::single('inventorydepth_queue')->insert_stock_update_queue($title,$params);
        }

        // 记录操作日志
        $optLogModel = $this->app->model('operation_log');
        $optLogModel->write_log('shop',$shop_id,'stockup','批量全局库存回写');

        $this->end(true,$this->app->_('成功插入队列!'));
    }

    public function exportTemplate(){

        $filename = "店铺商品分配模板".date('Y-m-d').".csv";
        $encoded_filename = urlencode($filename);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);
        $ua = $_SERVER["HTTP_USER_AGENT"];
        header("Content-Type: text/csv");
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox$/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $pObj = &$this->app->model('shop_adjustment');
        $title = $pObj->exportTemplate('title');
        echo '"'.implode('","',$title).'"';
    }

    /**
     * 读取发布后的状态
     *
     * @return void
     * @author
     **/
    public function getResult()
    {
        $id = $_POST['id'];
        if(!$id) $this->splash('error',null,$this->app->_('数据为空!'));

        $adjustmentModel = $this->app->model('shop_adjustment');

        $release_status = $adjustmentModel->select()->columns('release_status')
                            ->where('id=?',$id)->instance()->fetch_one();
        if ($release_status == 'success') {
            $this->splash('finish',null,$this->app->_('发布成功'));
        }elseif($release_status == 'fail'){
            $this->splash('finish',null,$this->app->_('发布失败'));
        }else{
            $this->splash('running',null,$this->app->_('运行中'));
        }
    }

    /**
     * @description 获取货品的应用规则
     * @access public
     * @param String $id
     * @return void
     */
    public function getApplyRegu($id)
    {
        if(!$id){ echo '';exit;}
        $sku = $this->app->model('shop_adjustment')->getList('shop_product_bn,bind,shop_id,shop_bn',array('id'=>$id),0,1);
        if(!$sku){ echo ''; exit;}
        $sku = $sku[0];

        if ($sku['bind'] == '1') {
            $rr = kernel::single('inventorydepth_logic_pkgstock')->getExecRegu($sku['shop_product_bn'],$sku['shop_id'],$sku['shop_bn']);
        } else {
            $rr = kernel::single('inventorydepth_logic_stock')->getExecRegu($sku['shop_product_bn'],$sku['shop_id'],$sku['shop_bn']);
        }

        echo <<<EOF
        <a href="index.php?app=inventorydepth&ctl=regulation&act=dialogEdit&p[0]={$rr['regulation_id']}&finder_id={$_GET['_finder']['finder_id']}&regulation_readonly=true" target="dialog::{title:'修改规则'}">{$rr['heading']}</a>
EOF;
    }

    /**
     * @description 获取前端店铺库存
     * @access public
     * @param void
     * @return void
     */
    public function getShopStock()
    {
        $iids = $_POST['iid'];$shop_id = $_POST['shop_id']; $shop_bn = $_POST['shop_bn'];$shop_type=$_POST['shop_type'];
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

        foreach ($result as $r) {
            if ($r['skus']) {
                foreach ($r['skus']['sku'] as $sku) {
                    $items[] = array(
                        'iid' => strval($r['iid']),
                        'sku_id' => $sku['sku_id'],
                        'num' => $sku['quantity'],
                        'id' => md5($shop_id.$r['iid'].$sku['sku_id']),
                    );
                }
            } else {
                $items[] = array(
                    'iid' => strval($r['iid']),
                    'num' => $r['num'],
                    'id' => md5($shop_id.$r['iid']),
                );
            }
        }

        $result = array('status'=>'succ','data'=>$items);
        echo json_encode($result);exit;
    }

    /**
     * @description 获取发布库存
     * @access public
     * @param void
     * @return void
     */
    public function getReleaseStock()
    {
        $ids = $_POST['ids'];$shop_id = $_POST['shop_id']; $shop_bn = $_POST['shop_bn'];
        if( !$ids || !$shop_id || !$shop_bn) {
            $result = array('status'=>'fail','msg'=>'参数为空!');
            echo json_encode($result);exit;
        }

        $adjustmentModel = $this->app->model('shop_adjustment');
        $skus = $adjustmentModel->getList('shop_product_bn,bind,shop_id,shop_bn,id,mapping',array('id'=>$ids));
        foreach ($skus as $sku) {
            $pbns[] = $sku['shop_product_bn'];

            if ($sku['bind'] == '1') {
                $bindpbs[] = $sku['shop_product_bn'];
            }
        }

        $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$pbns));

        if ($bindpbs) {
            $pkgProducts = array();
            $shopPkg = array();
            $pkgGoods = app::get('omepkg')->model('pkg_goods')->getList('pkg_bn,goods_id',array('pkg_bn'=>$bindpbs));
            $pkgGoodId = array();
            foreach ($pkgGoods as $key=>$pkg) {
                $pkgGoodId[] = $pkg['goods_id'];
                $shopPkg[$pkg['pkg_bn']]=array($pkg['goods_id']);
            }
            if ($pkgGoodId) {
                $pkgProductId = app::get('omepkg')->model('pkg_product')->getList('product_id,bn,name,goods_id',array('goods_id'=>$pkgGoodId));
                $ppid = array();
                foreach ($pkgProductId as $key=>$value) {
                    $ppid[] = $value['product_id'];
                    $pkgProducts[$value['goods_id']][]=array('bn'=>$value['bn'],'name'=>$value['name']);
                }
                $productspkg = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('product_id'=>$ppid));

                $products = array_filter(array_merge_recursive((array)$products,(array)$productspkg));

            }
        }

        if(!$products){
            $result = array('status'=>'fail','msg'=>'无关联货号!');

            echo json_encode($result);exit;
        }

        # 捆绑商品写内存
        kernel::single('inventorydepth_stock_pkg')->writeMemory($products);
        kernel::single('inventorydepth_stock_products')->writeMemory($products);

        $data = array();
        foreach ($skus as $sku) {

            if ($sku['bind'] == '1') {

                //print_r($shopPkg);
                # 发布库存
                $quantity = kernel::single('inventorydepth_logic_pkgstock')->dealWithRegu($sku['shop_product_bn'],$sku['shop_id'],$sku['shop_bn'],$rr);

                # 可售库存
                $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_id']);

                #捆绑明细库存
                $goods_id = $shopPkg[$sku['shop_product_bn']][0];

                $pkgProducts_list = $pkgProducts[$goods_id];
                $actual_product_stock = array();
                if($pkgProducts_list){
                    foreach($pkgProducts_list as $pkglist){
                        $stock = kernel::single('inventorydepth_stock_calculation')->get_actual_stock($pkglist['bn'],$sku['shop_bn'],$sku['shop_id']);
                        $actual_product_stock[] =
                            array(
                            'bn'=>$pkglist['bn'],
                            'stock'=>$stock,
                            'product_name'=>$pkglist['name'],
                            );
                    }
                }


            } else {
                $quantity = kernel::single('inventorydepth_logic_stock')->dealWithRegu($sku['shop_product_bn'],$sku['shop_id'],$sku['shop_bn'],$rr);
                $actual_stock = kernel::single('inventorydepth_stock_calculation')->get_actual_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_id']);
            }
            if($actual_stock === false) continue;

            if ($quantity !== false) {
                $adjustmentModel->update(array('release_stock'=>$quantity),array('id'=>$sku['id']));
            }

            if ($sku['mapping'] =='1') {
                $reguhtml = <<<EOF
                <a href="index.php?app=inventorydepth&ctl=regulation&act=dialogEdit&p[0]={$rr['regulation_id']}&regulation_readonly=true" target="dialog::{title:'修改规则'}">{$rr['heading']}</a>
EOF;
            } else {
                $reguhtml = '-';
            }

            $data[] = array(
                'id' => $sku['id'],
                'quantity' => $quantity,
                'actual_stock' => $sku['mapping']=='1' ? $actual_stock : '-',
                'actual_product_stock'=>$sku['bind']=='1' ? $actual_product_stock :'',
                'reguhtml' => $reguhtml,
            );
        }

        $result = array('status'=>'succ','data'=>$data);
        echo json_encode($result);exit;
    }

}
