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
class customs_finder_orders
{
    var $type_list = array();
    function __construct()
    {
        $oCustoms    = app::get('customs')->model('orders');
        $this->type_list    = $oCustoms->get_typename();
    }
    
    /*------------------------------------------------------ */
    //-- 编辑
    /*------------------------------------------------------ */
    var $column_edit  = '操作';
    var $column_edit_order = 5;
    var $column_edit_width = '50';
    function column_edit($row)
    {
        if($row['status'] == 1)
        {
            return '';
        }
        elseif($row['status'] == 2)
        {
            return '';
        }
        elseif($row['status'] == 3)
        {
            return '';
        }
        elseif($row['status'] == 4)
        {
            return '';
        }
        else
        {
            return '<a href="index.php?app=customs&ctl=admin_orders&act=editor&p[0]='.$row['cid'].'&finder_id='.$_GET['_finder']['finder_id'].'" 
                    target="dialog::{width:700,height:590,title:\'编辑跨境申报信息\'}">编辑</a>';
        }
    }
    
    /*------------------------------------------------------ */
    //-- 详细列表
    /*------------------------------------------------------ */
    var $detail_customs    = '跨境申报详情';
    function detail_customs($cid)
    {
        $oOrder     = &app::get('ome')->model('orders');
        $oCustoms   = &app::get('customs')->model('orders');
        $render     = &app::get('customs')->render();
        
        $cid        = intval($cid);
        $data       = array();
        $data       = $oCustoms->dump($cid, '*');
        $order_id   = $data['order_id'];
        
        #订单详情
        $order_detail    = $oOrder->dump($order_id, '*');
        $order_detail['is_cods']    = $oOrder->modifier_is_cod($order_detail['is_cod']);
        $render->pagedata['orders']    = $order_detail;
        
        #来源店铺
        $sql           = "SELECT b.name FROM ".DB_PREFIX."ome_orders as a LEFT JOIN ".DB_PREFIX."ome_shop as b ON a.shop_id=b.shop_id 
                         WHERE a.order_id='".$order_id."'";
        $shop_data    = kernel::database()->select($sql);
        $data['shop_name']    = $shop_data[0]['name'];
        
        #购物网站
        $type_list    = $oCustoms->get_typename();
        $data['shop_type']    = $type_list['shop_type'][$data['shop_type']]['shop_name'];
        
        #支付方式
        $data['payment']    = $type_list['payment'][$data['payment']];
        
        #币种
        $data['currency']    = $type_list['currency'][$data['currency']];
        
        #隐藏身份证号
        $data['card_no']    = ($data['card_no'] ? substr_replace($data['card_no'], '***', -8, 4) : '');
        
        #备注
        $mark_text    = kernel::single('ome_func')->format_memo($data['remarks']);
        
        $render->pagedata['mark_text']   = $mark_text;
        $render->pagedata['item']        = $data;
        return $render->fetch('admin/order_detail.html');
    }
    
    /*------------------------------------------------------ */
    //-- 订单明细
    /*------------------------------------------------------ */
    var $detail_goods = '订单明细';
    function detail_goods($cid)
    {
        $render    = &app::get('customs')->render();
        $oOrder    = &app::get('ome')->model('orders');
        $oCustoms  = &app::get('customs')->model('orders');
        $cid       = intval($cid);
        
        $data        = $oCustoms->dump($cid, 'order_id, order_bn');
        $order_id    = $data['order_id'];
        
        $item_list = $oOrder->getItemList($order_id, true);
        $item_list = ome_order_func::add_getItemList_colum($item_list);
        ome_order_func::order_sdf_extend($item_list);
        
        $orders = $oOrder->getRow(array('order_id'=>$order_id),'shop_type,order_source');
        $is_consign = false;
        
        #淘宝代销订单增加代销价
        if($orders['shop_type'] == 'taobao' && $orders['order_source'] == 'tbdx' ){
            kernel::single('ome_service_c2c_taobao_order')->order_sdf_extend($item_list);
            $is_consign = true;
        }
        
        $configlist = array();
        if ($servicelist = kernel::servicelist('ome.service.order.products'))
        foreach ($servicelist as $object => $instance){
            if (method_exists($instance, 'view_list')){
                $list = $instance->view_list();
                $configlist = array_merge($configlist, is_array($list) ? $list : array());
            }
        }
        
        $render->pagedata['is_consign'] = ($is_consign > 0)?true:false;
        $render->pagedata['configlist'] = $configlist;
        $render->pagedata['item_list'] = $item_list;
        $render->pagedata['object_alias'] = $oOrder->getOrderObjectAlias($order_id);
        return $render->fetch('admin/detail_goods.html');
    }
    
    /*------------------------------------------------------ */
    //-- 申报操作日志
    /*------------------------------------------------------ */
    var $detail_history = '申报操作日志';
    function detail_history($cid)
    {
        $render    = &app::get('customs')->render();
        $oOrder    = &app::get('ome')->model('orders');
        $logObj    = &app::get('ome')->model('operation_log');
        $oCustoms  = &app::get('customs')->model('orders');
        $cid       = intval($cid);
        
        $data        = $oCustoms->dump($cid, 'order_id, order_bn');
        $order_id    = $data['order_id'];
        
        /* 本订单日志 */
        $history    = $logObj->read_log(array('obj_id'=>$order_id, 'obj_type'=>'orders@customs'), 0, -1);
        foreach($history as $k=>$v)
        {
            $history[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        
        $render->pagedata['history'] = $history;
        $render->pagedata['order_id'] = $order_id;
        
        return $render->fetch('admin/detail_history.html');
    }
    
    /*------------------------------------------------------ */
    //-- 格式化字段
    /*------------------------------------------------------ */
    #购物网站
    var $column_shop_type = '购物网站';
    var $column_shop_type_width = '80';
    var $column_shop_type_order = 30;
    function column_shop_type($row)
    {
        if(!empty($row['currency']))
        {
            $shop_type        = $this->type_list['shop_type'][$row['shop_type']]['shop_name'];
        }
        
        return $shop_type;
    }
    
    #货币
    var $column_currency = '货币';
    var $column_currency_width = '100';
    var $column_currency_order = 50;
    function column_currency($row)
    {
        if(!empty($row['currency']))
        {
            $currency    = $this->type_list['currency'][$row['currency']];
        }
        
        return $currency;
    }
    
    #支付方式
    var $column_payment = '支付方式';
    var $column_payment_width = '80';
    var $column_payment_order = 60;
    function column_payment($row)
    {
        if(!empty($row['payment']))
        {
            $payment    = $this->type_list['payment'][$row['payment']];
        }
        
        return $payment;
    }
    
    #物流公司
    var $column_logis = '物流公司';
    var $column_logis_width = '80';
    var $column_logis_order = 70;
    function column_logis($row)
    {
        if(!empty($row['logis_id']))
        {
            $logis    = $this->type_list['logistics'][$row['logis_id']];
        }
        
        return $logis;
    }
    
    #来源店铺
    var $column_source_shop = '来源店铺';
    var $column_source_shop_width = '100';
    var $column_source_shop_order = 25;
    function column_source_shop($row)
    {
        $sql       = "SELECT b.name FROM ".DB_PREFIX."ome_orders as a LEFT JOIN ".DB_PREFIX."ome_shop as b ON a.shop_id=b.shop_id 
                     WHERE a.order_id='".$row['order_id']."'";
        $data      = kernel::database()->select($sql);
        
        return $data[0]['name'];
    }
    
    #申报单状态
    var $column_declare_status = '申报单状态';
    var $column_declare_status_width = '80';
    var $column_declare_status_order = 70;
    function column_declare_status($row)
    {
        if(!empty($row['declare_status']))
        {
            $status    = $this->type_list['declare_status'][$row['declare_status']];
        }
        
        return $status;
    }
    
    #确认状态
    var $column_process_status = '确认状态';
    var $column_process_status_width = '80';
    var $column_process_status_order = 54;
    function column_process_status($row)
    {
        return ($row['process_status'] == 'splited' ? '<b style="color:#ff0000;">已拆分完</b>' : '未确认');
    }
    
    #发货状态
    var $column_ship_status = '发货状态';
    var $column_ship_status_width = '80';
    var $column_ship_status_order = 54;
    function column_ship_status($row)
    {
        return ($row['ship_status'] == '1' ? '<b style="color:#ff0000;">已发货</b>' : '未发货');
    }
    
    /*------------------------------------------------------ */
    //-- 显示行样式[粗体：highlight-row]
    //-- [加绿：list-even 加黄：selected 加红：list-warning]
    /*------------------------------------------------------ */
    function row_style($row)
    {
        $style = '';
        
        if($_GET['act'] == 'declare_list')
        {
            if($row[$this->col_prefix . 'declare_check'] == '1')
            {
                $style .= ' list-even ';//申报完成
            }
        }
        else 
        {
            if($row[$this->col_prefix . 'status'] == '2')
            {
                $style .= ' highlight-row ';
            }
            elseif($row[$this->col_prefix . 'status'] == '1')
            {
                $style .= ' list-even ';
            }
            elseif($row[$this->col_prefix . 'status'] == '0' && $row[$this->col_prefix . 'disabled'] == 'true')
            {
                $style .= ' list-warning ';
            }
            elseif($row[$this->col_prefix . 'disabled'] == 'true')
            {
                $style .= ' selected ';
            }
        }
        
        return $style;
    }
}