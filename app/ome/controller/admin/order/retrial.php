<?php
/**
 +----------------------------------------------------------
 * 订单复审&财审
 +----------------------------------------------------------
 * Author: ExBOY
 * Time: 2014-04-30 $
 * [Ecos!] (C)2003-2014 Shopex Inc.
 +----------------------------------------------------------
 */
class ome_ctl_admin_order_retrial extends desktop_controller
{
    var $order_type     = 'index';
    
    /*------------------------------------------------------ */
    //-- 修改待复审订单[列表]
    /*------------------------------------------------------ */
    function index()
    {
        $this->title    = '商品变化订单';
        $base_filter['retrial_type']   = 'normal';
        
        $params         = 
                array('title'=>$this->title,
                    'use_buildin_set_tag'=>true,
                    'use_buildin_filter'=>true,
                    'use_buildin_tagedit'=>true,
                    'use_buildin_export'=>false,
                    'use_buildin_import'=>false,
                    'allow_detail_popup'=>true,
                    'use_buildin_recycle'=>false,
                    'use_view_tab'=>true,
                    'base_filter' => $base_filter,
                );

        $this->finder('ome_mdl_order_retrial', $params);
    }
    /*------------------------------------------------------ */
    //-- 修改待复审订单[列表]
    /*------------------------------------------------------ */
    function audit()
    {
        $this->title    = '价格异常订单';
        $this->order_type   = 'audit';
        
        $base_filter['retrial_type']   = 'audit';
        
        $params         = 
                array('title'=>$this->title,
                    'use_buildin_set_tag'=>true,
                    'use_buildin_filter'=>true,
                    'use_buildin_tagedit'=>true,
                    'use_buildin_export'=>false,
                    'use_buildin_import'=>false,
                    'allow_detail_popup'=>true,
                    'use_buildin_recycle'=>false,
                    'use_view_tab'=>true,
                    'base_filter' => $base_filter,
                );
        
        $this->finder('ome_mdl_order_retrial', $params);
    }
    /*------------------------------------------------------ */
    //-- 已复审核列表
    /*------------------------------------------------------ */
    function success()
    {
        $this->title        = '已复审订单';
        $this->order_type   = 'success';
        
        if(empty($_GET['view']))
        {
            $base_filter['status']   = array('1', '2', '3');
        }
        
        $this->finder('ome_mdl_order_retrial',
              array('title'=>$this->title,
                    'use_buildin_set_tag'=>true,
                    'use_buildin_filter'=>true,
                    'use_buildin_tagedit'=>true,
                    'use_buildin_export'=>false,
                    'use_buildin_import'=>false,
                    'allow_detail_popup'=>true,
                    'use_buildin_recycle'=>false,
                    'use_view_tab'=>true,
                    'base_filter' => $base_filter,
                ));
    }
    /*------------------------------------------------------ */
    //-- 分类导航
    /*------------------------------------------------------ */
    function _views()
    {
        #操作员
        $op_id     = kernel::single('desktop_user')->get_id();
        $is_super  = kernel::single('desktop_user')->is_super();
        
        #
        $mdl_order     = $this->app->model('order_retrial');
        if($this->order_type == 'success')
        {
           $sub_menu = $this->_views_success();
        }
        elseif($this->order_type == 'audit')
        {
           $sub_menu = $this->_views_audit();
        }
        else
        {
           $sub_menu = $this->_views_index();
        }
        
        $i=0;
        foreach($sub_menu as $k => $v)
        {
            $sub_menu[$k]['filter'] = $v['filter']?$v['filter']:null;
            $sub_menu[$k]['addon'] = $mdl_order->viewcount($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl='.$_GET['ctl'].'&act='.$this->order_type.'&view='.$i++;
        }
        
        return $sub_menu;
    }
    function _views_index()
    {
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'), 'filter'=>array('retrial_type'=>'normal'), 'optional'=>false),
            1 => array('label'=>app::get('base')->_('待复审'), 'filter'=>array('status'=>'0', 'retrial_type'=>'normal'), 'optional'=>false),
            2 => array('label'=>app::get('base')->_('复审通过'), 'filter'=>array('status'=>'1', 'retrial_type'=>'normal'), 'optional'=>false),
            3 => array('label'=>app::get('base')->_('复审未通过'), 'filter'=>array('status'=>array('2', '3'), 'retrial_type'=>'normal'), 'optional'=>false),
        );
        return $sub_menu;
    }
    function _views_audit()
    {
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'), 'filter'=>array('retrial_type'=>'audit'), 'optional'=>false),
            1 => array('label'=>app::get('base')->_('待复审'), 'filter'=>array('status'=>'0', 'retrial_type'=>'audit'), 'optional'=>false),
            2 => array('label'=>app::get('base')->_('复审通过'), 'filter'=>array('status'=>'1', 'retrial_type'=>'audit'), 'optional'=>false),
            3 => array('label'=>app::get('base')->_('复审未通过'), 'filter'=>array('status'=>'2', 'retrial_type'=>'audit'), 'optional'=>false),
        );
        return $sub_menu;
    }
    function _views_success()
    {
        $sub_menu = array(
            0 => array('label'=>app::get('base')->_('全部'), 'filter'=>array('status'=>array('1', '2')), 'optional'=>false),
            1 => array('label'=>app::get('base')->_('复审通过'), 'filter'=>array('status'=>'1'), 'optional'=>false),
            2 => array('label'=>app::get('base')->_('复审未通过'), 'filter'=>array('status'=>array('2', '3')), 'optional'=>false),
        );
        return $sub_menu;
    }
    /*------------------------------------------------------ */
    //-- 复审
    /*------------------------------------------------------ */
    function normal($id)
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        $filter['id']   = $id;
        $is_super       = kernel::single('desktop_user')->is_super();
        $op_id          = kernel::single('desktop_user')->get_id();
        
        #复审订单详情
        $oRetrial  = &app::get('ome')->model('order_retrial');
        $row       = $oRetrial->getList('*', $filter, 0, 1);
        $row       = $row[0];
        $this->pagedata['row']  = $row;
        
        if(empty($row))
        {
            exit('没有相关记录。。。');
        }
        
        #
        if($row['retrial_type'] == 'audit')
        {
            //订单信息&&价格监控
            $datalist   = $oRetrial->get_order_monitor($row['order_id']);
            
            $this->pagedata['order_profit']         = $datalist['order_profit'];
            $this->pagedata['price_monitor']        = $datalist['price_monitor'];
            $this->pagedata['monitor_flag']         = $datalist['monitor_flag'];
            $this->pagedata['setting_is_monitor']   = $datalist['setting_is_monitor'];
            
            $this->pagedata['order']       = $datalist['order'];
            
            $this->singlepage('admin/order/retrial_audit.html');
        }
        else
        {
            //订单与订单快照信息&&价格监控
            $datalist   = $oRetrial->contrast_order($row['order_id'], $row['id']);

            $this->pagedata['order_profit']         = $datalist['order_profit'];
            $this->pagedata['old_price_monitor']    = $datalist['old_price_monitor'];
            $this->pagedata['new_price_monitor']    = $datalist['new_price_monitor'];
            $this->pagedata['monitor_flag']         = $datalist['monitor_flag'];
            $this->pagedata['setting_is_monitor']   = $datalist['setting_is_monitor'];

            $this->pagedata['order_old']    = $datalist['order_old'];
            $this->pagedata['order_new']    = $datalist['order_new'];
            
            $this->singlepage('admin/order/retrial_normal.html');
        }
    }
    /*------------------------------------------------------ */
    //-- 复审审核提交
    /*------------------------------------------------------ */
    function save()
    {
        $this->begin('');
        
        $id        = intval($_POST['id']);
        $verify    = trim($_POST['verify']);
        $remarks   = addslashes($_POST['remarks']);
        
        $filter['id']  = $id;
        $new_order     = array();
        
        #超级管理员
        $is_super   = kernel::single('desktop_user')->is_super();
        $op_id              = kernel::single('desktop_user')->get_id();
        
        #复审订单详情
        $oRetrial  = &app::get('ome')->model('order_retrial');
        $row       = $oRetrial->getList('*', $filter, 0, 1);
        $row       = $row[0];
        if(empty($row))
        {
            $this->end(false, '数据不存在，请检查后重新提交。');
        }
        if(empty($remarks) || strlen($remarks)<5)
        {
            $this->end(false, '无效操作，复审备注描述不清楚。');
        }
        
        #复审审核结果
        $oOrder    = &app::get('ome')->model('orders');
        if($verify == 'success')
        {
           //读取快照订单的状态
           $oSnap      = &app::get('ome')->model('order_retrial_snapshot');
           $snapRow    = $oSnap->getList('*', array('retrial_id'=>$row['id']), 0, 1);
           $snapRow    = $snapRow[0];
           $snapRow['order_detail']        = unserialize($snapRow['order_detail']);

           //审核成功，更新订单的状态
           $new_order['process_status']    = 'unconfirmed';//审核成功后，不能使用快照中的确认状态
           $new_order['abnormal']          = 'false';
           $new_order['pause']             = 'false';//手动设置默认
           $new_order['confirm']           = 'N';
           
           $oOrder->update($new_order, array('order_id'=>$snapRow['order_id'], 'abnormal'=>'true', 'process_status'=>'is_retrial'));

           $new_retrial['status']     = '1';//审核'通过'状态
           $message                   = '<span style="color:#00CC00;">审核通过</span>';
           
           //[更新]订单异常表状态
           $oRetrial->update_abnormal($snapRow['order_id']);
           
           //[更新]商品冻结库存
           $oRetrial->confirm_stock_freeze($row['id']);
        }
        else
        {
            //审核未通过，更新订单的状态为'暂停'
            $new_order['pause']   = 'true';//暂停状态
            $oOrder->update($new_order, array('order_id'=>$row['order_id'], 'abnormal'=>'true', 'process_status'=>'is_retrial'));
            
            $new_retrial['status']     = '2';//审核'不通过'状态
            $message                   = '<span style="color:#ff0000;">审核未通过</span>';
        }
        
        #更新操作员[op_id]
        if(empty($row['op_id']))
        {
            $row['op_id']  = $op_id;
        }
        
        #UPDATE
        $new_retrial['remarks']     = $remarks;
        $new_retrial['lastdate']    = time();        
        $sql        = "UPDATE ". DB_PREFIX ."ome_order_retrial SET status='".$new_retrial['status']."', op_id='".$row['op_id']."', remarks='".$new_retrial['remarks']."', 
                    lastdate='".$new_retrial['lastdate']."' WHERE id='".$row['id']."'";
        kernel::database()->exec($sql);
        
        #复审订单关联的退款单操作 2014.08.13
        $oRetrial->oper_ome_refund_apply($row['order_id'], $verify);
        
        //日志
        $oOperation_log        = &app::get('ome')->model('operation_log');
        $oOperation_log->write_log('order_retrial@ome', $row['order_id'], $remarks.' '.$message);
        
        $this->end(true, '订单'.$message);
    }
    /*------------------------------------------------------ */
    //-- [价格]复审审核提交
    /*------------------------------------------------------ */
    function save_audit()
    {
        $this->begin('');
        
        $id        = intval($_POST['id']);
        $verify    = trim($_POST['verify']);
        $remarks   = addslashes($_POST['remarks']);
        
        $filter['id']   = $id;
        $is_super       = kernel::single('desktop_user')->is_super();
        $op_id          = kernel::single('desktop_user')->get_id();
        $new_order      = array();
        
        #复审订单详情
        $oRetrial  = &app::get('ome')->model('order_retrial');
        $row       = $oRetrial->getList('*', $filter, 0, 1);
        $row       = $row[0];
        if(empty($row))
        {
            $this->end(false, '数据不存在，请检查后重新提交。');
        }
        if(empty($remarks) || strlen($remarks)<5)
        {
            $this->end(false, '无效操作，复审备注描述不清楚。');
        }

        #复审审核结果
        $oOrder    = &app::get('ome')->model('orders');
        if($verify == 'success')
        {
           //审核成功，更新订单的状态
           $new_order['process_status']    = 'unconfirmed';
           $new_order['abnormal']          = 'false';
           $new_order['pause']             = 'false';
           $oOrder->update($new_order, array('order_id'=>$row['order_id'], 'abnormal'=>'true', 'process_status'=>'is_retrial'));

           $new_retrial['status']     = '1';//审核'通过'状态
           $message                   = '<span style="color:#00CC00;">价格审核通过</span>';
           
           #[更新]订单异常表状态
           $oRetrial->update_abnormal($row['order_id']);
        }
        else
        {
            //审核未通过，更新订单的状态为'暂停'
            $new_order['pause']   = 'true';//暂停状态
            $oOrder->update($new_order, array('order_id'=>$row['order_id'], 'abnormal'=>'true', 'process_status'=>'is_retrial'));
            
            $new_retrial['status']     = '2';//审核'不通过'状态
            $message                   = '<span style="color:#ff0000;">价格审核未通过</span>';
        }
        
        #更新操作员[op_id]
        if(empty($row['op_id']))
        {
            $row['op_id']  = $op_id;
        }
        
        #UPDATE
        $new_retrial['remarks']     = $remarks;
        $new_retrial['lastdate']    = time();        
        $sql        = "UPDATE ". DB_PREFIX ."ome_order_retrial SET status='".$new_retrial['status']."', op_id='".$row['op_id']."', remarks='".$new_retrial['remarks']."', 
                    lastdate='".$new_retrial['lastdate']."' WHERE id='".$row['id']."'";
        kernel::database()->exec($sql);
        
        //日志
        $oOperation_log        = &app::get('ome')->model('operation_log');
        $oOperation_log->write_log('order_retrial@ome', $row['order_id'], $remarks.' '.$message);
        
        $this->end(true, '订单'.$message);
    }
    /*------------------------------------------------------ */
    //-- 回滚订单
    /*------------------------------------------------------ */
    function rollback()
    {
        $this->begin('');
        
        $id     = intval($_POST['id']);
        $op_id  = kernel::single('desktop_user')->get_id();
        
        #复审订单详情
        $oRetrial  = &app::get('ome')->model('order_retrial');
        $row       = $oRetrial->getList('id, order_id, order_bn, retrial_type, status', array('id'=>$id, 'status'=>'2'), 0, 1);
        $row       = $row[0];
        if(empty($row))
        {
            $this->end(false, '数据不存在，请检查后重新提交。');
        }
        
        #订单详情
        $filter['order_id']       = $row['order_id'];
        $filter['abnormal']       = 'true';
        $filter['process_status'] = 'is_retrial';
        
        $oOrder     = &app::get('ome')->model('orders');
        $result     = $oOrder->getList('order_id, order_bn', $filter, 0, 1);
        $result     = $result[0];
        if(empty($result))
        {
            $this->end(false, '订单不存在，请检查后重新提交。');
        }
        
        #订单回滚
        $flag     = $oRetrial->rollback_order($row['id'], $row['order_id']);
        $message  = $flag ? '成功' : '失败';
        
        #复审订单关联的退款单_回滚操作 2014.08.13
        if($flag)
        {
            $oRetrial->oper_ome_refund_apply($row['order_id'], 'rollback');
        }
        
        $this->end(true, '回滚订单'.$message);
    }
    /*------------------------------------------------------ */
    //-- 显示订单快照
    /*------------------------------------------------------ */
    function show_operation($id)
    {
        header("cache-control:no-store,no-cache,must-revalidate");
        $id    = intval($_GET['id']);
        if(empty($id))
        {
            die('无效操作;');
        }
        
        #快照
        $oSnapshot  = &app::get('ome')->model('order_retrial_snapshot');
        $snapList   = $oSnapshot->getList('*', array('tid'=>$id), 0, 1);
        $snapList   = $snapList[0];
        
        if(empty($snapList))
        {
            die('没有相关记录;');
        }
        $detail     = unserialize($snapList['order_detail']);

        //发货人信息
        if(empty($detail['consigner']['name']))
        {
            $sObj      = &app::get('ome')->model('shop');
            $shop_info = $sObj->getList('*',array('shop_id'=>$detail['shop_id']));
            $shop_info = $shop_info[0];
            $detail['consigner']['name'] = $shop_info['default_sender'];
            $detail['consigner']['area'] = $shop_info['area'];
            $detail['consigner']['addr'] = $shop_info['addr'];
            $detail['consigner']['zip'] = $shop_info['zip'];
            $detail['consigner']['email'] = $shop_info['email'];
            $detail['consigner']['tel'] = $shop_info['tel'];
        }
        
        //购买人信息
        $memberObj = &app::get('ome')->model('members');
        $members_detail = $memberObj->dump($detail['member_id']);
        $this->pagedata['member']   = $members_detail;
        
        $this->pagedata['operation_detail']    = $detail;
        $this->singlepage('admin/order/retrial_show_operation.html');
    }
}