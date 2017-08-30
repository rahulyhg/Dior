<?php
/**
 * 商品明细controller
 *
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_ctl_shop_items extends desktop_controller {

    var $workground = 'goods_manager';
    var $defaultWorkground = 'goods_manager';
    
    function __construct($app)
    {
        parent::__construct($app);
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

        $params = array(
            'title' => $title,
            'actions' => array(
                1 => array('label' => $this->app->_('上架'),'target' => 'dialog::{title:\'批量上架\'}','submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=approveQueueNotice&p[0]=onsale'),
                2 => array('label' => $this->app->_('下架'),'target' => 'dialog::{title:\'批量下架\'}','submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=approveQueueNotice&p[0]=instock'),
                # 3 => array('label' => $this->app->_('开启上下架'),'submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=set_frame&p[0]=true','target'=>'refresh'),
                # 4 => array('label' => $this->app->_('关闭上下架'),'submit'=>'index.php?app=inventorydepth&ctl=shop_items&act=set_frame&p[0]=false','target'=>'refresh')
            ),
            'use_buildin_export' => true,
            'use_buildin_recycle' => false,
            'use_buildin_filter' => true,
            'base_filter' => $base_filter,
        );

        $this->finder('inventorydepth_mdl_shop_items',$params);
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
            1 => array('label' => $this->app->_('在架数'),'addon'=>'','href'=>'','filter'=>array('approve_status'=>'onsale')),
            2 => array('label' => $this->app->_('下架数'),'addon'=>'','href'=>'','filter'=>array('approve_status'=>'instock')),
        );
        $itemsModel = $this->app->model('shop_items');
        foreach ($views as $key => $view) {
            $view['filter']['shop_id'] = $_REQUEST['shop_id'];
            $views[$key]['addon'] = $itemsModel->count($view['filter']);
            $views[$key]['href'] = 'index.php?app=inventorydepth&ctl=shop_items&act=index&view='.$key.'&shop_id='.$_REQUEST['shop_id'];
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
        $m = $approve_status=='onsale' ? '上架' : '下架';
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

        // 记录操作日志
        $optLogModel = app::get('inventorydepth')->model('operation_log');
        $optLogModel->write_log('item',$item[0]['id'],'approve',($approve_status=='onsale' ? '单个上架' : '单个下架'));


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
        $view = $this->_views();
        if (isset($_POST['view']) && $view[$_POST['view']]['filter']) {
            $_POST = array_merge((array)$_POST,(array)$view[$_POST['view']]['filter']); unset($_POST['view']);
        }

        $this->pagedata['post'] = http_build_query($_POST);
        $this->pagedata['approve_status'] = $approve_status;
        $this->page('shop/items/approve_in_queue.html');
    }

    /**
     * 批量上下架
     *
     * @return void
     * @author 
     **/
    public function batchApprove($approve_status)
    {
        $post = $_POST;
        foreach ($post as $key=>$value) {
            if ($value === '') {
                unset($post[$key]);
            }
        }


        $this->begin();
        if (!$post['id'] && $post['isSelectedAll'] != '_ALL_' ) {
            $this->end(false,$this->app->_('参数错误!'));
        }

        if (!in_array($approve_status, array('onsale','instock'))) {
            $this->end(false,$this->app->_('请选择上下架类型!'));
        }
        
        if (!$_SESSION['shop_id']) {
            $this->end(false,$this->app->_('请选择店铺!'));
        }
        $post['shop_id'] = $_SESSION['shop_id'];

        $itemModel = $this->app->model('shop_items');
        # 判断是否允许回写 

        # 具体操作
        $itemModel->appendCols = '';
        $itemModel->filter_use_like = true;
        $shops = $itemModel->getList('distinct shop_id,shop_name',$post);
        foreach ($shops as $shop) {
            $post['shop_id'] = $shop['shop_id'];
            $count = $itemModel->count($post);
            if ($count <= 0) {
                continue;
            }

            $title = "批量店铺【{$shop['shop_name']}】商品上下架";

            $params = $post;$limit = 100;
            $params['limit'] = $limit;
            $params['do_approve'] = $approve_status;

            // 操作员信息
            $params['operInfo'] = kernel::single('inventorydepth_func')->getDesktopUser();

            $total = floor($count/$limit);
            for ($i=$total; $i>=0 ; $i--) {
                $params['offset'] = $i*$limit;

                # 插入队列
                kernel::single('inventorydepth_queue')->insert_approve_queue($title,$params);
            }
            
        }

        $this->end(true,$this->app->_('插入队列成功!'));
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

}
