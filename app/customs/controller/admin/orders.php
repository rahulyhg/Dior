<?php
/**
 +----------------------------------------------------------
 * 跨境申报管理
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2015-04-18 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class customs_ctl_admin_orders extends desktop_controller
{
    var $order_type = 'all';
    
    public function __construct($app)
    {
        parent::__construct($app);
    }
    /*------------------------------------------------------ */
    //-- 列表
    /*------------------------------------------------------ */
    function index()
    {
        #csv导入新订单
        if($_GET['action'] == 'import')
        {
            $_GET['ctler']    = 'customs_mdl_orders';
            $_GET['add']      = 'customs';
            
            $render = kernel::single('desktop_controller');
            $render->pagedata['ctler'] = $_GET['ctler'];
            $render->pagedata['add'] = $_GET['add'];
            $render->pagedata['_finder'] = $_GET['_finder']['finder_id'];
            $render->pagedata['finder_id'] = $_GET['finder_id'];
            
            $get = kernel::single('base_component_request')->get_get();
            try {
                $oName = substr($get['ctler'],strlen($get['add'].'_mdl_'));
                $model = app::get($get['add'])->model( $oName );
            } catch (Exception $e) {
                $msg = $e->getMessage();
                echo $msg;exit;
            }
            unset($get['app'],$get['ctl'],$get['act'],$get['add'],$get['ctler']);
            $render->pagedata['data'] = $get;
            
            if (method_exists($model, 'import_input')) {
                $render->pagedata['import_input'] = $model->import_input();
            }
            
            $this->display('admin/import_order.html');
            exit;
        }
        
        $this->title    = '跨境申报列表';
        $base_filter    = array();
        $actions        = array();
        $use_buildin_import    = false;//导入
        
        switch($_GET['view'])
        {
            case '1':
                $actions    = array(
                                array(
                                    'label'=>app::get('customs')->_('申请申报单号'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=declare_bn",
                                    'target'=>'dialog::{width:500,height:150,title:\'申请申报单号\'}"'
                                ),
                            );
                break;
            
            case '2':
                $actions    = array(
                                array(
                                    'label'=>app::get('customs')->_('进行申报'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=apply",
                                    'target'=>'dialog::{width:500,height:150,title:\'申报跨境订单\'}"'
                                ),
                                array(
                                    'label'=>app::get('customs')->_('撤消申报'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=cancel",
                                    'target'=>'dialog::{width:500,height:150,title:\'撤消跨境订单\'}"'
                                ),
                            );
            break;
            case '3':
                $actions    = array(
                                array(
                                    'label'=>app::get('customs')->_('重新待申报'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=anew",
                                    'target'=>'dialog::{width:500,height:150,title:\'重新待申报\'}"'
                                ),
                                array(
                                        'label'=>app::get('customs')->_('还原为普通订单'),
                                        'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=normal",
                                        'target'=>'dialog::{width:500,height:150,title:\'还原为普通订单\'}"'
                                ),
                            );
                break;
            case '4':
                $actions    = array(
                                array(
                                    'label'=>app::get('customs')->_('已拒绝'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=index",
                                    'target'=>'dialog::{width:500,height:150,title:\'已拒绝\'}"'
                                ),
                            );
                break;
            case '5':
                $actions    = array(
                                array(
                                    'label'=>app::get('customs')->_('申报单状态查询'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=sel_status",
                                    'target'=>'dialog::{width:500,height:150,title:\'申报单状态查询\'}"'
                                ),
                                array(
                                    'label'=>app::get('customs')->_('(按时间)查询申报单状态'),
                                    'href'=>"index.php?app=customs&ctl=admin_orders&act=sel_date_status",
                                    'target'=>'dialog::{width:500,height:150,title:\'(按时间)查询申报单状态\'}"'
                                ),
                            );
                break;
            default:
                $actions    = array(
                                array(
                                        'label'=>app::get('customs')->_('还原为普通订单'),
                                        'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=normal",
                                        'target'=>'dialog::{width:500,height:150,title:\'还原为普通订单\'}"'
                                ),
                                array(
                                        'label'=>app::get('customs')->_('下载跨境订单模板'),
                                        'href'=>'index.php?app=customs&ctl=admin_orders&act=exportTemplate',
                                        'target'=>'_blank"'
                                ),
                );
                
                $use_buildin_import    = true;
                break;
        }
        
        $params    = array(
                        'actions' => $actions,
                        'title' => $this->title,
                        'use_buildin_set_tag'=>false,
                        'use_buildin_filter'=>true,
                        'use_buildin_tagedit'=>true,
                        'use_buildin_import' => $use_buildin_import,
                        'use_buildin_export'=>true,
                        'allow_detail_popup'=>false,
                        'use_buildin_recycle'=>false,
                        'use_view_tab'=>true,
                        'base_filter' => $base_filter,
                    );
        
        $this->finder('customs_mdl_orders', $params);
    }

    /*------------------------------------------------------ */
    //-- 分类导航
    /*------------------------------------------------------ */
    function _views()
    {
        if($this->order_type == 'adopt')
        {
            $sub_menu = $this->_viewAdopt();
        }
        else 
        {
            $sub_menu = $this->_viewsAll();
        }
        
        return $sub_menu;
    }
    
    /*------------------------------------------------------ */
    //-- 申报订单列表
    /*------------------------------------------------------ */
    function _viewsAll()
    {
        $mdl_order    = $this->app->model('orders');
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'), 'filter'=>array(), 'optional'=>false),
                1 => array('label'=>app::get('base')->_('待申报'), 'filter'=>array('status'=>'0'), 'optional'=>false),
                2 => array('label'=>app::get('base')->_('申报中'), 'filter'=>array('status'=>'2'), 'optional'=>false),
                3 => array('label'=>app::get('base')->_('已撤消'), 'filter'=>array('status'=>'3'), 'optional'=>false),
                4 => array('label'=>app::get('base')->_('已拒绝'), 'filter'=>array('status'=>'4'), 'optional'=>false),
                5 => array('label'=>app::get('base')->_('已申报'), 'filter'=>array('status'=>'1'), 'optional'=>false),
        );
        
        $i=0;
        foreach($sub_menu as $k => $v)
        {
            $sub_menu[$k]['filter']   = $v['filter'] ? $v['filter']:null;
            $sub_menu[$k]['addon']    = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href']     = 'index.php?app=customs&ctl='.$_GET['ctl'].'&act=index&view='.$i++;
        }
        
        return $sub_menu;
    }
    
    /*------------------------------------------------------ */
    //-- 申报单状态列表
    /*------------------------------------------------------ */
    function _viewAdopt()
    {
        $mdl_order    = $this->app->model('orders');
        $sub_menu = array(
                0 => array('label'=>app::get('base')->_('全部'), 'filter'=>array('status'=>'1'), 'optional'=>false),
                1 => array('label'=>app::get('base')->_('审核中'), 'filter'=>array('status'=>'1', 'declare_check' => '0'), 'optional'=>false),
                2 => array('label'=>app::get('base')->_('进行中'), 'filter'=>array('status'=>'1', 'declare_check' => '1'), 'optional'=>false),
                3 => array('label'=>app::get('base')->_('已完成'), 'filter'=>array('status'=>'1', 'declare_status' => '24'), 'optional'=>false),
        );
        
        $i=0;
        foreach($sub_menu as $k => $v)
        {
            $sub_menu[$k]['filter']   = $v['filter'] ? $v['filter']:null;
            $sub_menu[$k]['addon']    = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href']     = 'index.php?app=customs&ctl='.$_GET['ctl'].'&act=declare_list&view='.$i++;
        }
        
        return $sub_menu;
    }
    
    /*------------------------------------------------------ */
    //-- 申报单状态列表
    /*------------------------------------------------------ */
    function declare_list()
    {
        $this->title      = '申报单状态列表';
        $this->order_type = 'adopt';
        
        $base_filter    = array('status' => 1);
        $actions        = array();
        
        switch($_GET['view'])
        {
            case '1':
                $actions    = array(
                                array(
                                    'label'=>app::get('customs')->_('更新申报单状态'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=get_status&view=1",
                                    'target'=>'dialog::{width:500,height:150,title:\'更新申报单状态\'}"',
                                ),
                );
                break;
            case '2':
                $actions    = array(
                                array(
                                    'label'=>app::get('customs')->_('更新申报单状态'),
                                    'submit'=>"index.php?app=customs&ctl=admin_orders&act=todeclare&action=get_status&view=2",
                                    'target'=>'dialog::{width:500,height:150,title:\'更新申报单状态\'}"',
                                ),
                );
                break;
            default:
                
                break;
        }
        
        $params    = array(
                'actions' => $actions,
                'title' => $this->title,
                'use_buildin_set_tag'=>false,
                'use_buildin_filter'=>false,
                'use_buildin_tagedit'=>true,
                'use_buildin_import' => false,
                'use_buildin_export'=>true,
                'allow_detail_popup'=>false,
                'use_buildin_recycle'=>false,
                'use_view_tab' => true,
                'base_filter' => $base_filter,
        );
        
        $this->finder('customs_mdl_orders', $params);
    }
    
    /*------------------------------------------------------ */
    //-- 编辑
    /*------------------------------------------------------ */
    function editor($cid)
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        
        $oCustoms   = app::get('customs')->model('orders');
        
        $cid        = intval($cid);
        $data       = array();
        
        $sql        = "SELECT a.*, b.shop_id, b.payed, b.cost_freight 
                            FROM ".DB_PREFIX."customs_orders as a 
                            LEFT JOIN ".DB_PREFIX."ome_orders as b ON a.order_id=b.order_id 
                            WHERE a.cid='".$cid."' AND a.status='0'";
        $data       = kernel::database()->select($sql);
        $data       = $data[0];
        
        #来源店铺
        $sql           = "SELECT b.name FROM ".DB_PREFIX."ome_orders as a LEFT JOIN ".DB_PREFIX."ome_shop as b ON a.shop_id=b.shop_id 
                         WHERE a.order_id='".$data['order_id']."'";
        $shop_data    = kernel::database()->select($sql);
        $data['shop_name']    = $shop_data[0]['name'];
        
        #购物网站
        $type_list    = $oCustoms->get_typename();
        
        #跨境店铺
        $sql           = "SELECT sid, company_code, company_name, bind_status FROM ".DB_PREFIX."customs_setting where bind_status='true' AND disabled='false'";
        $customs_shop  = kernel::database()->select($sql);
        
        $data['t_end']    = ($data['t_end'] ? date('Y-m-d H:i:s', $data['t_end']) : '');
        
        $this->pagedata['customs_shop']    = $customs_shop;
        $this->pagedata['type_list']    = $type_list;
        $this->pagedata['item']         = $data;
        $this->page('admin/order_editor.html');
    }

    /*------------------------------------------------------ */
    //-- 保存
    /*------------------------------------------------------ */
    function save()
    {
        $this->begin('');
        $oCustoms        = &app::get('customs')->model('orders');
        $oOperation_log  = &app::get('ome')->model('operation_log');
        
        $data              = $row = array();
        $data              = $_POST['item'];
        $data['cid']       = intval($data['cid']);
        $data['lastdate']  = time();
        
        #检查提交数据有效性
        $data['payment_bn']    = trim($data['payment_bn']);
        if (empty($data['payment_bn']))
        {
            $this->end(false, '请填写支付单号');
        }
        
        $data['card_no']    = trim($data['card_no']);
        if(empty($data['card_no']))
        {
            $this->end(false, '请填写身份证号');
        }
        
        $data['member_name']    = trim($data['member_name']);
        if (empty($data['member_name']) || !preg_match("/([\x81-\xfe][\x40-\xfe])/", $data['member_name'], $match))
        {
            $this->end(false, '申报人姓名,必须为汉字');
        }
        if(empty($data['member_mobile']) && empty($data['member_tel']))
        {
            $this->end(false, '请填写申报人手机号码 或者 联系电话');
        }
        if(empty($data['member_email']))
        {
            $this->end(false, '请输入有效的E_mail');
        }
        if(empty($data['logis_id']))
        {
            $this->end(false, '请选择物流公司');
        }
        if(empty($data['shop_sid']))
        {
            $this->end(false, '请选择所属跨境店铺');
        }
        
        #select
        $row        = $oCustoms->dump($data['cid'], '*');
        if($row['status'] !== '0')
        {
            $this->end(false, '订单已经申报，不能再次编辑');
        }
        
        #update
        $sql    = "UPDATE `sdb_customs_orders` set payment_bn='".$data['payment_bn']."', `shop_type`= '".$data['shop_type']."', `payment`= '".$data['payment']."', 
                   `currency`= '".$data['currency']."', `lastdate`= '".$data['lastdate']."', `card_no`= '".$data['card_no']."', 
                   member_name='".$data['member_name']."', member_mobile='".$data['member_mobile']."', member_tel='".$data['member_tel']."', 
                   member_email='".$data['member_email']."', logis_id='".$data['logis_id']."', shop_sid='".$data['shop_sid']."' 
                   where cid='".$data['cid']."'";
        $result = kernel::database()->exec($sql);
        if($result)
        {
            //查检订单是否有效
            $filter['order_id'][0]    = $row['order_id'];
            $oCustoms->check_decalre($filter, true);
            
            //日志
            $log_msg   = '订单编辑成功';
            $oOperation_log->write_log('customs_edit@ome', $row['order_id'], '订单编辑成功');
            $this->end(true, $log_msg);
        }
        else
        {
            $this->end(false, '信息更新失败');
        }
    }
    
    /*------------------------------------------------------ */
    //-- 按时间查询"申报单状态"
    /*------------------------------------------------------ */
    function sel_date_status()
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        
        $time_from   = time() - (86400 * 7);
        $time_from   = date('Y-m-d', $time_from);
        $time_to     = date('Y-m-d', time());
        
        $dateline    = array('time_from' => $time_from, 'time_to' => $time_to);
        $this->pagedata['dateline'] = $dateline;
        
        $this->page('admin/sel_date_status.html');
    }
    
    /*------------------------------------------------------ */
    //-- 申请申报单号step 1
    /*------------------------------------------------------ */
    function todeclare()
    {
        $oCustoms   = &app::get('customs')->model('orders');
        
        $this->_request    = kernel::single('base_component_request');
        $data              = $this->_request->get_post();
        if(empty($data['cid']))
        {
            echo '请选择申报订单!';
            exit;
        }
        
        $view      = trim($_GET['view']);
        $action    = trim($_GET['action']);
        if(empty($action))
        {
            echo '操作错误!';
            exit;
        }
        
        $templet   = '';//加载模板
        $filter    = array('cid'=>$data['cid']);
        switch ($action)
        {
            case 'declare_bn':
                $filter['status']      = '0';
                $filter['declare_bn']  = '';
                
                $templet        = 'admin/batch_declare_bn.html';
                
                //查检订单是否有效
                $sel_where['cid']    = $data['cid'];
                $oCustoms->check_decalre($sel_where, true);
                break;
            case 'apply':
                $filter['status']      = '2';
                
                $templet        = 'admin/batch_apply.html';
                break;
            case 'cancel':
                $filter['status']      = '2';
                
                $templet        = 'admin/batch_cancel.html';
                break;
           case 'anew':
                $filter['status']      = '3';
                
                $templet        = 'admin/batch_anew.html';
                break;
           case 'normal':
                $filter['status']      = '3';
                
                $templet        = 'admin/batch_normal.html';
                break;
           case 'sel_status':
               $filter['status']      = '1';
               
               $templet        = 'admin/sel_status.html';
               break;
           case 'sel_date_status':
               $filter['status']      = '1';
               
               $templet        = 'admin/sel_date_status.html';
               break;
           case 'get_status':
               $filter['status']      = '1';
               
               $templet        = 'admin/sel_status.html';
               break;
        }
        
        #查询可申报订单
        if($action == 'normal')
        {
            $sql    = "SELECT count(*) AS num FROM ". DB_PREFIX ."customs_orders WHERE cid in(".implode(',', $data['cid']).") 
                       AND status in('0', '3')";
            $count  = kernel::database()->selectrow($sql);
            $count  = $count['num'];
        }
        else 
        {
            $count         = $oCustoms->count($filter);
        }
        
        #无效订单
        if($action == 'declare_bn')
        {
            $filter['disabled']    = 'true';
            $fail_count            = $oCustoms->count($filter);
        }
        $this->pagedata['fail_count']  = intval($fail_count);
        
        $limit    = 50;
        if($count > $limit)
        {
            echo '已选择 '.$count.' 个订单，系统每次允许批量申报 '.$limit.' 个订单!';
            exit;
        }
        
        #统计批量支付订单数量
        $this->pagedata['count']  = $count;
        $this->pagedata['action'] = $action;//执行动作
        $this->pagedata['view']   = $view;
        $this->pagedata['cid']    = serialize($data['cid']);
        $this->display($templet);
    }
    
    /*------------------------------------------------------ */
    //-- 确认申请申报单号step 2
    /*------------------------------------------------------ */
    function dodeclare()
    {
        $this->begin('index.php?app=customs&ctl=admin_orders&act=index');
        
        $cid        = array();
        if(!empty($_POST['cid']))
        {
            $cid    = unserialize($_POST['cid']);
        }
        if(empty($cid))
        {
            $this->end(false,'提交数据有误!');
        }
        
        $view      = trim($_GET['view']);
        $action    = trim($_GET['action']);
        if(empty($action))
        {
            echo '操作错误!';
            exit;
        }
        
        $templet   = '';//加载模板
        $filter    = array();
        switch ($action)
        {
            case 'declare_bn':
                $filter['status']   = '0';
                $templet            = 'admin/controllertmpl/get_declare_bn.html';
                break;
            case 'apply':
                $filter['status']   = '2';
                $templet            = 'admin/controllertmpl/apply_declare.html';
                break;
            case 'cancel':
                $filter['status']   = '2';
                $templet            = 'admin/controllertmpl/cancel_declare.html';
                break;
            case 'sel_status':
                $filter['status']   = '1';
                $templet            = 'admin/controllertmpl/status_declare.html';
                break;
            case 'get_status':
                $filter['status']   = '1';
                $templet            = 'admin/controllertmpl/status_declare.html';
                break;
        }
        
        #获取订单
        $db         = kernel::database();
        $dataList   = array();
        
        $sql        = "SELECT cid FROM ".DB_PREFIX."customs_orders as a WHERE a.cid in(".implode(',', $cid).") AND a.status='".$filter['status']."'";
        $dataList   = $db->select($sql);
        
        $cid    = array();
        foreach ($dataList as $key => $val)
        {
            $cid[]    = $val['cid'];
        }
        if(empty($cid))
        {
            $this->end(false,'没有提交有效的订单!');
        }
        
        $this->pagedata['action']     = $action;//执行动作
        $this->pagedata['view']       = $view;
        $this->pagedata['post_ids']   = json_encode($cid);
        $this->pagedata['count']      = count($cid);
        $this->display($templet);
        exit;
    }
    
    /*------------------------------------------------------ */
    //-- Ajax循环进行申请申报单号step 3
    /*------------------------------------------------------ */
    function ajax_declare()
    {
        $request_uri = kernel::single('base_component_request')->get_request_uri();
        
        $view      = trim($_GET['view']);
        $action    = trim($_GET['action']);
        if(empty($action))
        {
            echo '操作错误!';
            exit;
        }
        
        $templet   = '';//加载模板
        $filter    = array();
        switch ($action)
        {
            case 'declare_bn':
                $templet            = 'admin/controllertmpl/async_declare_bn.html';
                $request_uri        .= '&view=1';
                break;
            case 'apply':
                $templet            = 'admin/controllertmpl/async_apply_declare.html';
                $request_uri        .= '&view=2';
                break;
            case 'cancel':
                $templet            = 'admin/controllertmpl/async_cancel_declare.html';
                $request_uri        .= '&view=2';
                break;
            case 'sel_status':
                $templet            = 'admin/controllertmpl/async_status_declare.html';
                $request_uri        .= '&view=5';
                break;
            case 'get_status':
                $templet            = 'admin/controllertmpl/async_get_status_declare.html';
                $request_uri        .= '&view=2';
                break;
        }
        
        $this->pagedata['request_uri'] = $request_uri;
        
        $ids    = explode(',', urldecode($_GET['itemIds']));
        $this->pagedata['postIds'] = json_encode($ids);
        
        $count = count($ids);
        $this->pagedata['count'] = $count;
        
        $this->pagedata['view']  = $view;
        $this->display($templet);
    }
    
    /*------------------------------------------------------ */
    //-- Ajax获取申报单号step 4
    /*------------------------------------------------------ */
    function async_declare_bn()
    {
        $oOperation_log  = &app::get('ome')->model('operation_log');
        $oOrder          = &app::get('ome')->model('orders');
        $oItems          = &app::get('ome')->model('order_items');
        $productObj      = &app::get('ome')->model('products');
        
        $oCustoms        = &app::get('customs')->model('orders');
        
        $cid    = intval($_POST['id']);
        
        #判断订单号是否有效
        $filter     = array('cid' => $cid);
        $field      = '*';
        $declareRow = $oCustoms->getList($field, $filter, 0, 1);
        $declareRow = $declareRow[0];
        
        $msg    = '';
        $result = array(
                'rsp' => 'fail',
                'order_id' => $declareRow['order_id'],
                'order_bn' => $declareRow['order_bn'],
        );
        
        if(empty($declareRow))
        {
            $msg    = '没有找到对应订单';
        }
        elseif($declareRow['status'] != '0')
        {
            $msg    = '订单号'.$declareRow['order_bn'].'已申报';
        }
        elseif($declareRow['declare_bn'] != '')
        {
            $msg    = '订单号'.$declareRow['order_bn'].'已存在申报单号';
        }
        if(!empty($msg))
        {
            $result['err_msg']    = $msg;
            echo json_encode($result);
            exit;
        }
        
        $order_id   = $declareRow['order_id'];
        $order_bn   = $declareRow['order_bn'];
        
        #订单详情
        $field       = '*';
        $orderRow    = $oOrder->dump(array('order_id'=>$order_id), $field);
        
        #收货人信息
        if(!empty($orderRow['consignee']['area']))
        {
            $region    = explode(':', $orderRow['consignee']['area']);
            $region    = explode('/', $region[1]);
        }
        $orderRow['consignee']['telephone']    = ($orderRow['consignee']['mobile'] ? $orderRow['consignee']['mobile'] : $orderRow['consignee']['telephone']);
        
        if(empty($region))
        {
            $result['err_msg']    = '订单相关信息不完整';
            echo json_encode($result);
            exit;
        }
        
        #电子口岸店铺
        $shop_sid    = $declareRow['shop_sid'];
        
        #回调参数
        $get_typename    = $oCustoms->get_typename();
        
        $shop_type       = $declareRow['shop_type'];
        $OrderShop       = $get_typename['shop_type'][$shop_type]['shop_name'];//购物网站
        $member_uname    = $declareRow['member_uname'];//买家账号
        
        if(empty($OrderShop) || empty($member_uname))
        {
            $result['err_msg']    = '买家账号、购物网站不能为空';
            echo json_encode($result);
            exit;
        }
        
        #联系电话 && 邮箱
        $phone    = ($declareRow['member_mobile'] ? $declareRow['member_mobile'] : $declareRow['member_tel']);
        $email    = $declareRow['member_email'];
        
        #Api参数
        $param = array(
            'Operation' => 0,# 0-新增,1-更新
            'OrderFrom' => $OrderShop,# 购物网站
            'PackageFlag' => '0',#是否组合装标识(0=不是，1=是)
            'BuyerAccount' => $member_uname,# 购物网站买家帐号
            'Phone' => $phone,# 手机号码
            'Email' => $email,# 邮箱
            
            'tid' => $order_bn,# 订单号
            'PostFee' => $orderRow['shipping']['cost_shipping'],# 运费
            'TaxAmount' => $orderRow['cost_tax'],# 税额
            'DisAmount' => abs($orderRow['discount']),# 优惠金额合计
            'Amount' => $orderRow['payed'],# 买家实付金额
        );
        
        #订单优惠清单列表[可为空]
        $param['Promotions'][]    = array(
                                            'ProAmount' => '',//优惠金额
                                            'ProRemark' => '',//优惠信息说明
                                          );
        $param['Promotions']      = json_encode($param['Promotions']);
        
        #商品明细
        $filter      = array('order_id'=>$order_id, 'delete' => 'false');
        $itemList    = $oItems->getList('item_id, bn, name, nums, price, amount, product_id', $filter);
        
        $param['good_list']    = array();
        foreach ($itemList as $key => $val)
        {
            #获取基础物料对应的单位
            $get_product    = $productObj->dump(array('product_id'=>$val['product_id']), 'unit');
            
            $val['unit']    = $get_product['unit'];//单位不能为空
            $param['good_list'][]    = array(
                                            'ProductId' => $val['bn'],//货号
                                            'GoodsName' => $val['name'],//商品名称
                                            'Qty' => $val['nums'],//购买数量
                                            'Unit' => $val['unit'],//单位
                                            'Price' => $val['price'],//单价
                                            'Amount' => $val['amount'],//总价
                                      );
        }
        $param['good_list']    = json_encode($param['good_list']);
        
        #支付信息
        $pay_info    = array(
                            'Paytime'=> date('Y-m-d H:i:s', $declareRow['t_end']),#支付时间
                            'PaymentNo'=> $declareRow['payment_bn'],#支付单号
                            'OrderSeqNo'=> $declareRow['payment_bn'],#商家送支付机构订单交易号
                            'Source'=> $declareRow['payment'],#支付方式代码
                       );
        $param['Pay']    = json_encode($pay_info);
        
        #运单信息
        $logisticsName    = $get_typename['logistics'][$declareRow['logis_id']];
        $logistics    = array(
                            'LogisticsNo' => '',#运单号
                            'LogisticsName' => $logisticsName,#快递公司名称
                            'Consignee' => $orderRow['consignee']['name'],# 收货人名称[可以是英文名称]
                            'Province' => $region[0],# 省
                            'City' => $region[1],# 城市
                            'District' => $region[2],# 区
                            'ConsigneeAddr' => $orderRow['consignee']['addr'],# 收货地址
                            'ConsigneeTel' => $orderRow['consignee']['telephone'],# 收货电话
                            'MailNo' => $orderRow['consignee']['zip'],# 邮编
                            'GoodsName' => '',#货物名称
                       );
        $param['Logistics']    = json_encode($logistics);
        
        #矩阵日志
        $writelog    = array(
                            'log_title' => '获取申报单号',//任务名称
                            'original_bn' => $order_bn,//单据号
                        );
        
        #Api实时请求
        $method    = 'store.cnec.jh.order';
        
        $nbRpcObj    = kernel::single('customs_rpc_request_ningbo');
        $rst         = $nbRpcObj->request($method, $param, array(), $shop_sid, $writelog);
        
        if($rst->rsp == 'succ')
        {
            $result    = json_decode($rst->data, true);
            $result    = $result['Message']['Header'];
            
            #回传的申报单号
            $ResultMsg    = $result['ResultMsg'];
            $MftNo        = $result['MftNo'];
            
            #更新申报单号
            $sql    = "UPDATE sdb_customs_orders SET status='2', declare_bn='".$MftNo."', lastdate=".time()." WHERE cid='".$cid."'";
            kernel::database()->exec($sql);
            
            #return
            $msg    = '获取成功,申报单号'.$MftNo;
            $result['rsp']    = 'succ';
            $result['msg']    = $msg;
        }
        else
        {
            $msg                = '获取失败,'.$rst->err_msg;
            $result['err_msg']  = $msg;
        }
        
        #操作日志
        $oOperation_log->write_log('customs_api@ome', $order_id, $msg);
        
        echo json_encode($result);
        exit;
    }
    
    /*------------------------------------------------------ */
    //-- 申请申报单号后刷新页面step 5
    /*------------------------------------------------------ */
    function refresh()
    {
        $backurl    = 'index.php?app=customs&ctl=admin_orders';
        
        if($_GET['action'] == 'declare_list')
        {
            $backurl    .= '&act='.$_GET['action'];
        }
        else 
        {
            $backurl    .= '&act=index';
        }
        
        if($_GET['view'])
        {
            $backurl    .= '&view='.intval($_GET['view']);
        }
        $this->begin($backurl);
        
        $this->end(true,'处理成功!');
    }
    
    /*------------------------------------------------------ */
    //-- Ajax申报跨境订单step 4
    /*------------------------------------------------------ */
    function async_apply_declare()
    {
        $oOperation_log  = &app::get('ome')->model('operation_log');
        $oOrder          = &app::get('ome')->model('orders');
        $oObjects        = &app::get('ome')->model('order_objects');
        $oCustoms        = &app::get('customs')->model('orders');
        
        $cid    = intval($_POST['id']);
        
        #判断订单号是否有效
        $filter     = array('cid' => $cid);
        $declareRow = $oCustoms->getList('*', $filter, 0, 1);
        $declareRow = $declareRow[0];
        
        $msg    = '';
        $result = array(
                'rsp' => 'fail',
                'order_id' => $declareRow['order_id'],
                'order_bn' => $declareRow['order_bn'],
        );
        
        if(empty($declareRow))
        {
            $msg    = '没有找到对应订单';
        }
        elseif($declareRow['status'] != '2')
        {
            $msg    = '订单号'.$declareRow['order_bn'].'已申报';
        }
        elseif(empty($declareRow['member_name']) || empty($declareRow['payment_bn']) || empty($declareRow['currency']) 
                || empty($declareRow['payment']) || empty($declareRow['card_no']))
        {
            $msg    = '订单号'.$declareRow['order_bn'].'信息不完整';
        }
        if(!empty($msg))
        {
            $result['err_msg']    = $msg;
            echo json_encode($result);
            exit;
        }
        
        $order_id   = $declareRow['order_id'];
        $order_bn   = $declareRow['order_bn'];
        $declareRow['member_mobile']    = ($declareRow['member_mobile'] ? $declareRow['member_mobile'] : $declareRow['member_tel']);
        
        #分类信息
        $type_list    = $oCustoms->get_typename();
        $logis_id     = ($declareRow['logis_id'] ? $declareRow['logis_id'] : 1);
        $declareRow['logis_name']    = $type_list['logistics'][$logis_id];//物流公司
        
        #订单详情
        $orderRow    = $oOrder->dump(array('order_id'=>$order_id), '*');
        
        #收货人信息
        if(!empty($orderRow['consignee']['area']))
        {
            $region    = explode(':', $orderRow['consignee']['area']);
            $region    = explode('/', $region[1]);
        }
        $orderRow['consignee']['telephone']    = ($orderRow['consignee']['mobile'] ? $orderRow['consignee']['mobile'] : $orderRow['consignee']['telephone']);
        
        if(empty($region))
        {
            $result['err_msg']    = '订单相关信息不完整';
            echo json_encode($result);
            exit;
        }
        
        #电子口岸店铺
        $shop_sid    = $declareRow['shop_sid'];
        
        #矩阵返回状态
        $apply_pay    = 0;
        $apply_lgs    = 0;
        
        #Api参数
        if($declareRow['apply_pay'] != '1')
        {
            $param = array(
                'MftNo' => $declareRow['declare_bn'],# 申报单号
                'PaymentNo' => $declareRow['payment_bn'],# 支付单号
                'OrderSeqNo' => $declareRow['payment_bn'],# 商家送支付机构订单交易号(如无,与支付单号一致)
                'CurrCode' => $declareRow['currency'],# 币种,目前RMB
                'Source' => $declareRow['payment'],# 支付方式代码
                
                'Idnum' => $declareRow['card_no'],# 身份证号码，大写
                'Name' => $declareRow['member_name'],# 真实姓名[必须是中文]
                'Phone' => $declareRow['member_mobile'],# 手机号码
                'Email' => $declareRow['member_email'],# 邮箱
                
                'Amount' => $orderRow['payed'],# 买家实付金额
            );
            
            #矩阵日志
            $writelog    = array(
                            'log_title' => '获取进口支付单',//任务名称
                            'original_bn' => $order_bn,//单据号
                        );
            
            #Api实时请求
            $method    = 'store.cnec.jh.pay';
            
            $nbRpcObj    = kernel::single('customs_rpc_request_ningbo');
            $rst         = $nbRpcObj->request($method, $param, array(), $shop_sid, $writelog);
            
            if($rst->rsp == 'succ')
            {
                $result    = json_decode($rst->data, true);
                $result    = $result['Message']['Header'];
                $ResultMsg = $result['ResultMsg'];
                
                #return
                $msg          = '成功,申请进口支付单';
                $apply_pay    = 1;//矩阵成功状态
            }
            else
            {
                $msg                = '失败,申请进口支付单 '.$rst->err_msg;
                $result['err_msg']  = $msg;
            }
            
            #操作日志
            $oOperation_log->write_log('customs_api@ome', $order_id, $msg);
            
            #有错误直接返回
            if(!empty($result['err_msg']))
            {
                echo json_encode($result);
                exit;
            }
        }
        
        #获取进口运单
        if($declareRow['apply_lgs'] != '1')
        {
            #订单商品明细
            $productList    = $oObjects->getList('obj_id, obj_type, bn, name', array('order_id' => $order_id), 0, -1);
            $product_str    = '';
            foreach ($productList as $key => $val)
            {
                if(strlen($product_str) > 950)
                {
                    continue;//组成字条串不能大于1000字符
                }
                $product_str    .= ','.$val['name'];
            }
            $product_str    = substr($product_str, 1);
            
            #Api参数
            $param = array(
                    'MftNo' => $declareRow['declare_bn'],# 申报单号
                    'LogisticsName' => $declareRow['logis_name'],# 快递公司名称
                    
                    'Consignee' => $orderRow['consignee']['name'],# 收货人名称[可以是英文名称]
                    'Province' => $region[0],# 省
                    'City' => $region[1],# 城市
                    'District' => $region[2],# 区
                    'ConsigneeAddr' => $orderRow['consignee']['addr'],# 收货地址
                    'ConsigneeTel' => $orderRow['consignee']['telephone'],# 收货电话
                    'MailNo' => $orderRow['consignee']['zip'],# 邮编
                    
                    'GoodsName' => $product_str,# 货物名称小于1000字符
            );
            
            #矩阵日志
            $writelog    = array(
                            'log_title' => '获取进口运单',//任务名称
                            'original_bn' => $order_bn,//单据号
                           );
            
            #Api实时请求
            $method    = 'store.cnec.jh.lgs';
            
            $nbRpcObj    = kernel::single('customs_rpc_request_ningbo');
            $rst         = $nbRpcObj->request($method, $param, array(), $shop_sid, $writelog);
            
            if($rst->rsp == 'succ')
            {
                $result    = json_decode($rst->data, true);
                $result    = $result['Message']['Header'];
                $ResultMsg = $result['ResultMsg'];
                
                #return
                $msg          = '成功,申请进口运单';
                $apply_lgs    = 1;//矩阵成功状态
            }
            else
            {
                $msg                = '失败,申请进口运单 '.$rst->err_msg;
                $result['err_msg']  = $msg;
            }
            
            #操作日志
            $oOperation_log->write_log('customs_api@ome', $order_id, $msg);
            
            #有错误直接返回
            if(!empty($result['err_msg']))
            {
                echo json_encode($result);
                exit;
            }
        }
        
        #成功后更新申报单状态
        $insert_str    = '';
        if($apply_pay)
        {
            $insert_str    .= ', apply_pay=1';
        }
        if($apply_lgs)
        {
            $insert_str    .= ', apply_lgs=1';
        }
        
        $status    = ($apply_pay == 1 && $apply_lgs == 1 ? 1 : 2);
        $sql       = "UPDATE sdb_customs_orders SET status='".$status."', lastdate=".time()."".$insert_str." WHERE cid='".$cid."'";
        kernel::database()->exec($sql);
        
        #return
        $msg              = '执行成功';
        $result['rsp']    = 'succ';
        $result['msg']    = $msg;
        
        echo json_encode($result);
        exit;
    }
    
    /*------------------------------------------------------ */
    //-- 撤消跨境订单 step:4
    /*------------------------------------------------------ */
    function async_cancel_declare()
    {
        $oOperation_log  = &app::get('ome')->model('operation_log');
        $oOrder          = &app::get('ome')->model('orders');
        $oObjects        = &app::get('ome')->model('order_objects');
        $oCustoms        = &app::get('customs')->model('orders');
        
        $cid    = intval($_POST['id']);
        
        #判断订单号是否有效
        $filter     = array('cid' => $cid);
        $declareRow = $oCustoms->getList('*', $filter, 0, 1);
        $declareRow = $declareRow[0];
        
        $msg    = '';
        $result = array(
            'rsp' => 'fail',
            'order_id' => $declareRow['order_id'],
            'order_bn' => $declareRow['order_bn'],
        );
        
        if(empty($declareRow))
        {
            $msg    = '没有找到对应订单';
        }
        elseif($declareRow['status'] != '2')
        {
            $msg    = '订单号'.$declareRow['order_bn'].'不能撤消操作';
        }
        elseif(empty($declareRow['declare_bn']))
        {
            $msg    = '订单号'.$declareRow['order_bn'].'信息不完整';
        }
        if(!empty($msg))
        {
            $result['err_msg']    = $msg;
            echo json_encode($result);
            exit;
        }
        
        $order_id   = $declareRow['order_id'];
        $order_bn   = $declareRow['order_bn'];
        
        #电子口岸店铺
        $shop_sid    = $declareRow['shop_sid'];
        
        #Api参数
        $param = array(
                    'MftNo' => $declareRow['declare_bn'],# 申报单号
                );
        
        #矩阵日志
        $writelog    = array(
                        'log_title' => '撤消跨境订单',//任务名称
                        'original_bn' => $order_bn,//单据号
                      );
        
        #Api实时请求
        $method    = 'store.cnec.jh.cancel';
        
        $nbRpcObj    = kernel::single('customs_rpc_request_ningbo');
        $rst         = $nbRpcObj->request($method, $param, array(), $shop_sid, $writelog);
        
        if($rst->rsp == 'succ')
        {
            $result    = json_decode($rst->data, true);
            $result    = $result['Message']['Header'];
            
            #更新申报单号
            $sql    = "UPDATE sdb_customs_orders SET status='3', lastdate=".time()." WHERE cid='".$cid."'";
            kernel::database()->exec($sql);
            
            #return
            $msg    = '撤消成功,申报单号'.$declareRow['declare_bn'];
            $result['rsp']    = 'succ';
            $result['msg']    = $msg;
        }
        else
        {
            $msg                = '撤消失败,'.$rst->err_msg;
            $result['err_msg']  = $msg;
        }
        
        #操作日志
        $oOperation_log->write_log('customs_api@ome', $order_id, $msg);
        
        echo json_encode($result);
        exit;
    }

    /*------------------------------------------------------ */
    //-- 重新申报已撤消的订单 step:5
    /*------------------------------------------------------ */
    function do_anew()
    {
        $this->begin('index.php?app=customs&ctl=admin_orders&act=index&view=4');
        
        $oOperation_log  = &app::get('ome')->model('operation_log');
        $oCustoms        = &app::get('customs')->model('orders');
        
        $cid        = array();
        if(!empty($_POST['cid']))
        {
            $cid    = unserialize($_POST['cid']);
        }
        if(empty($cid))
        {
            $this->end(false,'提交数据有误!');
        }
        
        #判断订单号是否有效
        $filter     = array('cid' => $cid, 'status' => '3');
        $result     = $oCustoms->getList('*', $filter, 0, -1);
        
        if(empty($result))
        {
            $this->end(false, '没有找到对应订单');
        }
        
        foreach ($result as $key => $val)
        {
            #更新为"待申报"
            $sql    = "UPDATE sdb_customs_orders SET declare_bn='', status='0', lastdate=".time()." WHERE cid='".$val['cid']."'";
            kernel::database()->exec($sql);
            
            #日志
            $oOperation_log->write_log('customs_edit@ome', $val['order_id'], '已撤消的订单,进行重新申报');
        }
        
        $this->end(true, '重新申报成功');
    }
    
    /*------------------------------------------------------ */
    //-- 还原为普通订单 step:6
    /*------------------------------------------------------ */
    function do_normal()
    {
        $this->begin('index.php?app=customs&ctl=admin_orders&act=index');
        
        $oOperation_log  = &app::get('ome')->model('operation_log');
        $oOrder          = &app::get('ome')->model('orders');
        $oAbnormal       = &app::get('ome')->model('abnormal');
        $oCustoms        = &app::get('customs')->model('orders');
        
        $cid        = array();
        if(!empty($_POST['cid']))
        {
            $cid    = unserialize($_POST['cid']);
        }
        if(empty($cid))
        {
            $this->end(false,'提交数据有误!');
        }
        
        #判断订单号是否有效
        $sql    = "SELECT order_id FROM ". DB_PREFIX ."customs_orders WHERE cid in(".implode(',', $cid).") 
                   AND status in('0', '3')";
        $result = kernel::database()->select($sql);
        
        if(empty($result))
        {
            $this->end(false, '没有找到对应订单');
        }
        
        foreach ($result as $key => $val)
        {
            $order_id    = $val['order_id'];
            
            #撤消为"普通订单"
            $update_order  = array();
            $update_order['order_id']          = $order_id;
            $update_order['process_status']    = 'unconfirmed';//未确认
            $update_order['pause']             = 'false';//订单暂停
            $update_order['abnormal']          = 'false';//异常
            
            $oOrder->save($update_order);
            
            #订单日志
            $oOperation_log->write_log('order_edit@ome', $order_id, '跨境申报还原为普通订单');
            
            #[撤消]订单异常状态
            $oAbnormal->update(array('is_done' => 'true'), array('order_id' => $order_id));
            
            #删除申报订单记录
            $oCustoms->delete(array('order_id' => $order_id));
            
            #删除申报订单日志
            $oOperation_log->delete(array('obj_id' => $order_id, 'obj_type' => 'orders@customs'));
        }
        
        $this->end(true, '还原为普通订单成功');
    }
    
    /*------------------------------------------------------ */
    //-- (按时间)申报状态查询 step:7
    /*------------------------------------------------------ */
    function do_date_status()
    {
        $this->begin('index.php?app=customs&ctl=admin_orders&act=index&view=5');
        $oCustoms        = &app::get('customs')->model('orders');
        $oOperation_log  = &app::get('ome')->model('operation_log');
        
        $time_from    = $_POST['time_from'];
        $time_to      = $_POST['time_to'];
        
        if(empty($time_from) || empty($time_to))
        {
            $this->end(false, '请选择查询时间范围');
        }
        
        $date_from    = strtotime($time_from.' 00:00:00');
        $date_to      = strtotime($time_to.' 23:59:59');
        
        if(($date_to - $date_from) > 604800)
        {
            $this->end(false, '查询时间范围只能是7天内时间间隔');
        }
        
        $sql          = "SELECT a.* FROM ".DB_PREFIX."customs_orders as a 
                            WHERE a.dateline >= ".$date_from." AND a.dateline <= ".$date_to." AND a.status='1'";
        $result       = kernel::database()->select($sql);
        if(empty($result))
        {
            $this->end(false, '没有相关记录');
        }
        
        #申报单状态
        $declare_type    = $oCustoms->get_typename('declare_status');
        
        #电子口岸店铺
        $shop_sid    = $result[0]['shop_sid'];
        
        #Api参数
        $param = array(
                    'StartTime' => date('Y-m-d H:i:s', $date_from),
                    'EndTime' => date('Y-m-d H:i:s', $date_to),
                );
        
        #矩阵日志
        $writelog    = array(
                        'log_title' => '(按时间)申报状态查询',//任务名称
                        'original_bn' => $result[0]['order_bn'],//单据号
                       );
        
        #Api实时请求
        $time_out  = 30;//秒
        $method    = 'store.cnec.jh.query';
        
        $nbRpcObj    = kernel::single('customs_rpc_request_ningbo');
        $rst         = $nbRpcObj->request($method, $param, array(), $shop_sid, $writelog, $time_out);
        
        if($rst->rsp == 'succ')
        {
            $result    = json_decode($rst->data, true);
            $return_list    = $result['Message']['Body']['Mft'];
            
            if(empty($return_list))
            {
                $this->end(false, '没有回传申报单记录');
            }
            
            #更新回传状态
            foreach ($return_list as $key => $val)
            {
                #返回数据
                $order_bn          = $val['OrderNo'];
                $declare_check     = ($val['CheckFlg'] == '1' ? '1' : '0');
                $declare_status    = $val['Status'];
                $logis_no          = $val['LogisticsNo'];#物流运单号
                
                if(empty($order_bn))
                {
                    continue;
                }
                
                $row    = $oCustoms->getList('*', array('order_bn' => $order_bn), 0, 1);
                $row    = $row[0];
                if(empty($row) || $row['status'] != '1')
                {
                    continue;
                }
                
                #更新申报单状态
                $sql    = "UPDATE sdb_customs_orders SET logis_no='".$logis_no."', declare_check='".$declare_check."', 
                          declare_status='".$declare_status."', lastdate=".time()." 
                          WHERE cid='".$row['cid']."'";
                kernel::database()->exec($sql);
                
                #操作日志
                $msg    = '申报单状态获取功(预检状态：'.($declare_check == '1' ? '已通过' : '未通过').', 申报状态：'.$declare_type[$declare_status].')';
                $oOperation_log->write_log('customs_api@ome', $row['order_id'], $msg);
            }
            
            $this->end(true, '成功查询申报单状态');
        }
        else
        {
            $this->end(false, '获取失败');
        }
    }
    
    /*------------------------------------------------------ */
    //-- (按订单)申报状态查询  step:8
    /*------------------------------------------------------ */
    function async_status_declare()
    {
        $oOperation_log  = &app::get('ome')->model('operation_log');
        $oCustoms        = &app::get('customs')->model('orders');
        
        $cid    = intval($_POST['id']);
        
        #判断订单号是否有效
        $filter     = array('cid' => $cid);
        $declareRow = $oCustoms->getList('*', $filter, 0, 1);
        $declareRow = $declareRow[0];
        
        $msg    = '';
        $result = array(
                        'rsp' => 'fail',
                        'order_id' => $declareRow['order_id'],
                        'order_bn' => $declareRow['order_bn'],
                    );
        
        if(empty($declareRow))
        {
            $msg    = '没有找到对应订单';
        }
        elseif($declareRow['status'] != '1')
        {
            $msg    = '订单号'.$declareRow['order_bn'].'不能撤消操作';
        }
        elseif(empty($declareRow['declare_bn']))
        {
            $msg    = '订单号'.$declareRow['order_bn'].'没有申报单号';
        }
        
        if(!empty($msg))
        {
            $result['err_msg']    = $msg;
            echo json_encode($result);
            exit;
        }
        
        $order_id   = $declareRow['order_id'];
        $order_bn   = $declareRow['order_bn'];
        
        #申报电子口岸详情
        $shop_sid    = $declareRow['shop_sid'];
        
        #Api参数
        $param = array(
                    'MftNo' => $declareRow['declare_bn'],# 申报单号
                );
        
        #矩阵日志
        $writelog    = array(
                        'log_title' => '(按订单)申报状态查询',//任务名称
                        'original_bn' => $order_bn,//单据号
                      );
        
        #Api实时请求
        $method    = 'store.cnec.jh.decl.byorder';
        
        $nbRpcObj    = kernel::single('customs_rpc_request_ningbo');
        $rst         = $nbRpcObj->request($method, $param, array(), $shop_sid, $writelog);
        
        if($rst->rsp == 'succ')
        {
            #回调参数
            $declare_type    = $oCustoms->get_typename('declare_status');
            
            #
            $result    = json_decode($rst->data, true);
            
            #返回信息
            $result_mft        = $result['Message']['Body']['Mft'];
            $declare_check     = ($result_mft['CheckFlg'] == '1' ? '1' : '0');
            $declare_status    = $result_mft['Status'];
            $logis_no          = $result_mft['LogisticsNo'];#物流运单号
            
            #更新申报单状态
            $sql    = "UPDATE sdb_customs_orders SET logis_no='".$logis_no."', declare_check='".$declare_check."', 
                       declare_status='".$declare_status."', lastdate=".time()." 
                       WHERE cid='".$cid."'";
            kernel::database()->exec($sql);
            
            #海关审核成功_订单自动审核发货
            if($declare_status == '24')
            {
                $order_rsp    = $oCustoms->order_auto_delivery($order_id);
            }
            
            #return
            $msg    = '申报单状态获取成功(预检标识：'.($declare_check == '1' ? '已通过' : '未通过').', 申报状态：'.$declare_type[$declare_status].')';
            $result['rsp']    = 'succ';
            $result['msg']    = $msg;
        }
        else
        {
            $msg                = '申报单状态获取失败,'.$rst->err_msg;
            $result['err_msg']  = $msg;
        }
        
        #操作日志
        $oOperation_log->write_log('customs_api@ome', $order_id, $msg);
        
        echo json_encode($result);
        exit;
    }
    
    /*------------------------------------------------------ */
    //-- [下载]参考导入跨境订单模板
    /*------------------------------------------------------ */
    function exportTemplate()
    {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        
        $oObj        = &app::get('ome')->model('orders');
        
        #订单
        $order_title      = $oObj->exportTemplate('order');
        
        #跨境
        $order_title[]    = kernel::single('base_charset')->utf2local('*:是否跨境订单');
        $order_title[]    = kernel::single('base_charset')->utf2local('*:付款状态');
        $order_title[]    = kernel::single('base_charset')->utf2local('*:支付单号');
        $order_title[]    = kernel::single('base_charset')->utf2local('*:会员身份证号');
        
        #商品
        $good_title       = $oObj->exportTemplate('obj');
        unset($good_title[8], $good_title[9], $good_title[10]);
        
        #演示数据
        $order_data    = array(
                            '201505180001', '支付宝', '2015/4/30  14:57:00', '2015/4/30  14:59:00', '快递', '0', '20141110001', 
                            '阿里巴巴演示订单', '王彤伟', '广东', '汕头市', '澄海区', '莱美工业区澄海潮商村镇银行营业部', '021-12345678', 
                            'women@126.com', '13502734369', '230022', '', '否', 'FP123456', '', '0', '0', '0', '', '', '150', '150',
                            'weco7369', '直销订单', '', '', '', '', '', '是', '已支付', '20150808001', '361126197905028000',
                        );
        
        foreach ($order_data as $key => $val)
        {
            if(!empty($order_data[$key]))
            {
                $order_data[$key]    = kernel::single('base_charset')->utf2local($order_data[$key]);
            }
        }
        
        $goods_data    = array('201505180001', '310520158908600001', '鲍鱼', '条', '', '1', '150', '150');
        foreach ($goods_data as $key => $val)
        {
            if(!empty($goods_data[$key]))
            {
                $goods_data[$key]    = kernel::single('base_charset')->utf2local($goods_data[$key]);
            }
        }
        
        #output
        echo '"'.implode('","', $order_title).'"';
        echo "\n";
        echo '"'.implode('","', $order_data).'"';
        echo "\n";
        echo '"'.implode('","', $good_title).'"';
        echo "\n";
        echo '"'.implode('","', $goods_data).'"';
    }
}
