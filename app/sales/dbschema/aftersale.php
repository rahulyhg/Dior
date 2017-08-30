<?php
$db['aftersale']=array(
  'columns' =>
  array(
    'aftersale_id' =>
    array(
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'aftersale_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '售后单号',
      'is_title' => true,
      'width' => 125,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => false,
    ),   
    'shop_id' => 
    array(
        'type' => 'table:shop@ome',
        'label' => '店铺名称',
        'width' => 120,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'filtertype' => 'normal',
        'filterdefault' => true,
        'order'=>1,
    ),
    'shop_bn' => 
    array (
      'type'  => 'varchar(20)',
      'label' => '店铺编号',
    ),
    'shop_name' =>
    array (
      'type' => 'varchar(255)',
      'label' => '店铺名称',
    ),
    'order_id' => 
    array( 
        'type' => 'table:orders@ome',
        'label' => '订单号',
        'width' => 140,
        'editable' => false,
        'in_list' => true,
        'searchtype' => 'has',
        //'default_in_list' => true,
        'order'=>2,
    ),
    'order_bn' => 
    array( 
        'type' => 'varchar(32)',
        'label' => '订单号',
    ),
    'return_id' => 
    array(
        'type' => 'table:return_product@ome',
        'label' => '售后申请单号',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order'=>3,
    ),
    'return_bn' => 
    array(
        'type' => 'varchar(32)',
        'label' => '售后申请单号',
    ),
    'reship_id' => 
    array(
        'type' => 'table:reship@ome',
        'label' => '退换货单号',
        'width' => 140,
        'searchtype' => 'has',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order'=>4,
    ),
    'reship_bn' => 
    array(
        'type' => 'varchar(32)',
        'label' => '退换货单号',
    ),
    'return_apply_id' => 
    array(
        'type' => 'table:refund_apply@ome',
        'label' => '退款申请单号',
        'width' => 140,
        'searchtype' => 'has',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order'=>5,
    ),
    'return_apply_bn' => 
    array(
        'type' => 'varchar(32)',
        'label' => '退款申请单号',
    ),
    'return_type' => 
    array(
        'type' =>
         array(
            'return' => '退货',
            'change' => '换货',
            'refund' => '退款',
            'refuse'=>'追回',
         ),
        'label' => '售后类型',
        'width' => 95,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'filtertype' => 'normal',
        'filterdefault' => true,
        'order'=>6,
    ),
    'refund_apply_money' => 
    array(
        'type' => 'money',
        'label' => '退款申请金额',
        'width' => 75,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'refundmoney' => 
    array(
        'type' => 'money',
        'label' => '已退款金额',
        'width' => 75,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'order'=>7,
    ),
    'paymethod' =>
    array (   
      'type' => 'varchar(100)',
      'label' => '退款支付方式',
      'width' => 110,
      'editable' => false,    
      #'filtertype' => 'normal',
      #'filterdefault' => true,
      'in_list' => true,
    ),    
    'member_id' => 
    array(
      'type' => 'table:members@ome',
      'required' => false,
      'editable' => false,
      'label' => '用户名',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 8,
      'width' => 130,
    ),
    'member_uname' => 
    array(
      'type' => 'varchar(50)',
      'label' => '用户名',
      'required' => false,
    ),
    'ship_mobile' => 
    array(
      'type' => 'varchar(50)',
      'required' => false,
      'editable' => false,
      'label' => '手机号',
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'order' => 9,
      'width' => 130,
    ),
    'add_time' => 
    array(
        'type' => 'time',
        'label' => '售后申请时间',
        'width' => 130,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'filtertype' => 'time',
        'filterdefault' => true,
        'order'=>10,
    ),
    'check_time' => 
    array(
        'type' => 'time',
        'label' => '审核时间',
        'width' => 130,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'filtertype' => 'time',
        'filterdefault' => true,
        'order'=>11,
    ),
    'acttime' => 
    array(
      'type' => 'time',
      'required' => false,
      'editable' => false,
      'label' => '质检时间',
      'filterdefault' => true,
      'filtertype' => 'time',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 12,
      'width' => 130,
    ),
    'refundtime' => 
    array(
      'type' => 'time',
      'required' => false,
      'editable' => false,
      'label' => '退款时间',
      'filterdefault' => true,
      'filtertype' => 'time',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 13,
      'width' => 130,
    ),
    'check_op_id' => 
    array(
      'type' => 'table:account@pam',
      'required' => false,
      'editable' => false,
      'label' => '审核人',
      'in_list' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
      'default_in_list' => true,
      'order' => 14,
      'width' => 130,
    ), 
    'check_op_name' => 
    array(
      'type' => 'varchar(32)',
      'label' => '审核人',
    ), 
    'op_id' => 
    array(
      'type' => 'table:account@pam',
      'required' => false,
      'editable' => false,
      'label' => '质检人',
      'in_list' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
      'default_in_list' => true,
      'order' => 15,
      'width' => 130,
    ), 
    'op_name' => 
    array(
      'type' => 'varchar(32)',
      'label' => '质检人',
    ), 
    'refund_op_id' => 
    array(
      'type' => 'table:account@pam',
      'required' => false,
      'editable' => false,
      'label' => '退款人',
      'in_list' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
      'default_in_list' => true,
      'order' => 16,
      'width' => 130,
    ), 
    'refund_op_name' => 
    array(
      'type' => 'varchar(32)',
      'label' => '退款人',
    ), 
    'aftersale_time' => 
    array(
      'type' => 'time',
      'required' => true,
      'editable' => false,
      'label' => '售后单据创建时间',
      'filterdefault' => true,
      'filtertype' => 'time',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 13,
      'width' => 130,
    ),
    'diff_order_bn' => array(
        'type' => 'varchar(32)',
        'label' => '补差价订单',
        'filterdefault' => true,
        'filtertype' => 'normal',
        'in_list' => true,
        'default_in_list' => true,
        'width' => 130,
    ),    
    'change_order_bn' => array(
        'type' => 'varchar(32)',
        'label' => '换货订单号',
        'filterdefault' => true,
        'filtertype' => 'normal',
        'in_list' => true,
        'default_in_list' => true,
        'width' => 130,
    ),    
    'pay_type' => 
    array (
      'type' => 
      array (
        'online' => '在线支付',
        'offline' => '线下支付',
        'deposit' => '预存款支付',
      ),
      'default' => 'online',
      'label' => '支付类型',
      'width' => 110,
      'editable' => false,
    ),
    'account' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'label' => '退款帐号',
    ),
    'bank' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '退款银行',
    ),
    'pay_account' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '收款帐号',
    ),
    'refund_apply_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '退款申请时间',
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'problem_name' =>
    array(
        'type' => 'varchar(200)',
        'label' => '售后服务类型',
        'filterdefault' => true,
        'filtertype' => 'normal',
        'in_list' => true,
        'default_in_list' => true,
        'width' => 130,
    ),
    'archive' =>
    array (
      'type' => 'tinyint unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '来源',
    ),
),
  'index' =>
  array(
    'ind_aftersale_time' =>
    array(
      'columns' =>
      array(
        0 => 'aftersale_time',
      ),
    ),
    'ind_refundtime' =>
    array(
      'columns' =>
      array(
        0 => 'refundtime',
      ),
    ),
    'ind_acttime' =>
    array(
      'columns' =>
      array(
        0 => 'acttime',
      ),
    ),
    'ind_check_time' =>
    array(
      'columns' =>
      array(
        0 => 'check_time',
      ),
    ),
    'ind_add_time' =>
    array(
      'columns' =>
      array(
        0 => 'add_time',
      ),
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
  'comment' => '售后单据',
);