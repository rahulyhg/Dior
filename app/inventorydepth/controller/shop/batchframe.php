<?php
/**
 * 上下架预警
 *
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_ctl_shop_batchframe extends desktop_controller {
    
    var $workground = 'goods_manager';
    var $defaultWorkground = 'goods_manager';

    function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * 上下架预警
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
        
        $expired = kernel::single('inventorydepth_batchframe')->is_expired($downloadTime);
        $notice = $expired ? '（数据已经过期，需重新同步！）' : '（上次同步时间：'.date('Y-m-d H:i:s',$downloadTime).'）';

        $shop = $this->app->model('shop')->getList('name', array('shop_id'=>$_SESSION['shop_id']));
        $title = "<span style='color:red;'>".$shop[0]['name']."</span>批量上下架".$notice;
        $_SESSION['shop_name'] = addslashes($shop[0]['name']);

        $params = array(
            'title' => $title,
            'actions' => array(
                1 => array('label' => $this->app->_('同步店铺数据'),'target' => 'dialog::{title:\'同步店铺数据\'}','href'=>'index.php?app=inventorydepth&ctl=shop&act=download_page&p[0]=shop&p[1]='.$_SESSION['shop_id'].'&redirectUrl[app]=inventorydepth&redirectUrl[ctl]=shop_batchframe&redirectUrl[act]=index'),
            ),
            'use_buildin_export' => false,
            'use_buildin_recycle' => false,
            'use_buildin_filter' => true,
            'base_filter' => $base_filter,
        );
        
        if ($_GET['view'] != '2' ) {
            $actions[1] = array('label' => $this->app->_('批量上架'),'target' => 'dialog::{title:\'批量上架\'}','submit'=>'index.php?app=inventorydepth&ctl=shop_batchframe&act=approveQueueNotice&p[0]=onsale');
        }
        if ($_GET['view'] != '1' ) {
            $actions[2] = array('label' => $this->app->_('批量下架'),'target' => 'dialog::{title:\'批量下架\'}','submit'=>'index.php?app=inventorydepth&ctl=shop_batchframe&act=approveQueueNotice&p[0]=instock');
        }

        if (!$expired) {
            $params['actions'] = array_merge($actions,$params['actions']);
        }

        if(!isset($_GET['action'])) {
            $panel = new desktop_panel($this);
            $panel->setId('frame_finder_top');
            $panel->setTmpl('finder/finder_panel_filter.html');
            $panel->show('inventorydepth_mdl_shop_batchframe', $params);
        }

        $this->finder('inventorydepth_mdl_shop_batchframe',$params);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function approveQueueNotice($approve_status)
    {
        $expired = kernel::single('inventorydepth_batchframe')->is_expired();
        if ($expired) {
            echo '<h1>数据已经过期！请先同步。<h1>';exit;
        }
        
        $view = $this->_views();
        if (isset($_POST['view']) && $view[$_POST['view']]['filter']) {
            $_POST = array_merge((array)$_POST,(array)$view[$_POST['view']]['filter']); unset($_POST['view']);
        }

        kernel::single('inventorydepth_ctl_shop_items')->approveQueueNotice($approve_status);
    }

    public function _views()
    {
        $views = array(
            0 => array('label' => $this->app->_('全部'),'addon'=>'','href'=>'','filter'=>array()),
            1 => array('label' => $this->app->_('需上架'),'addon'=>'','href'=>'','filter'=>array('approve_status'=>'instock','taog_store|than'=>0)),
            2 => array('label' => $this->app->_('需下架'),'addon'=>'','href'=>'','filter'=>array('approve_status'=>'onsale','taog_store'=>0)),
        );
        $itemsModel = $this->app->model('shop_items');
        foreach ($views as $key => $view) {
            $view['filter']['shop_id'] = $_SESSION['shop_id'];
            $views[$key]['addon'] = $itemsModel->count($view['filter']);
            $views[$key]['href'] = 'index.php?app=inventorydepth&ctl=shop_batchframe&act=index&view='.$key;
        }
        return $views;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function redownload()
    {
        if($_POST['shop_id']) {
            $_SESSION['shop_id'] = $_POST['shop_id'];
        } elseif($_GET['filter']['shop_id']) {
            $_SESSION['shop_id'] = $_GET['filter']['shop_id'];
        }

        $expired = kernel::single('inventorydepth_batchframe')->is_expired();
        if($expired){
            $redirectUrl = 'index.php?app=inventorydepth&ctl=shop&act=download_page&p[0]=shop&p[1]='.$_SESSION['shop_id'].'&redirectUrl[app]=inventorydepth&redirectUrl[ctl]=shop_batchframe&redirectUrl[act]=index';
            $update = true;
            $this->splash('success',null,null,'redirect',array('expired'=>$expired,'redirectUrl'=>$redirectUrl,'update'=>$update));
        }else{
            $this->index();
        }
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function approve_loading($id,$approve_status) 
    {
        $expired = kernel::single('inventorydepth_batchframe')->is_expired();
        if ($expired) {
            echo '<h1>数据已经过期！请先同步商品。<h1>';exit;
        }
        kernel::single('inventorydepth_ctl_shop_items')->approve_loading($id,$approve_status);
    }

}
