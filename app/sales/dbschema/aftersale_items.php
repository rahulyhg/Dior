<?php
$db['aftersale_items'] = array(
  'columns' =>
  array(
    'item_id' => array(
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
      'comment' => '明细ID',
      ),
    'aftersale_id' => 
    array(
      'type' => 'table:aftersale@sales',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),  
    'bn' =>
    array(
      'type' => 'varchar(30)',
      'editable' => false,
      'required' => false,
      'comment' => '货号',
    ),
    'product_name' =>
    array(
      'type' => 'varchar(200)',
      'editable' => false,
      'comment' => '货品名称',
    ),
    'product_id' =>
    array (
      'type' => 'int unsigned',
      'editable' => false,
      'comment' => '货品ID',
    ),
    'num' =>
    array(
      'type' => 'number',
      'editable' => false,
      'required' => false,
      'default' => 1,
      'comment' => '数量',
    ),
    'price' =>
    array(
      'type' => 'money',
      'default' => '0',
      'required' => false,
      'editable' => false,
      'comment' => '单价',
    ),
    'saleprice' =>
    array(
      'type' => 'money',
      'default' => '0',
      'required' => false,
      'editable' => false,
      'comment' => '销售价',
    ),
    'branch_id' =>
    array(
      'type' => 'table:branch@ome',
      'editable' => false,
      'label'=>'仓库ID',
      'comment'=>'仓库ID',
    ),
    'branch_name' =>
    array(
      'type' => 'varchar(200)',
      'editable' => false,
      'label'=>'仓库名称',
      'comment'=>'仓库名称',
    ),
    'return_type' =>
    array(
      'type' =>
      array(
        'return' => '退货',
        'change' => '换货',
            'refunded' => '退款',
             'refuse'=>'追回',
      ),
      'default' => 'return',
      'required' => true,
      'comment' => '售后类型',
      'editable' => false,
      'label' => '售后类型',
    ),
    'pay_type' => 
    array(
      'type' => 
      array(
        'online' => '在线支付',
        'offline' => '线下支付',
        'deposit' => '预存款支付',
      ),
      'default' => 'online',
      'required' => false,
      'label' => '支付类型',
      'width' => 110,
      'editable' => false,
    ),
    'account' =>
    array(
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '退款帐号',
    ),
    'bank' =>
    array(
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '退款银行',
    ),
    'pay_account' =>
    array(
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '收款帐号',
    ),
    'money' =>
    array(
      'type' => 'money',
      'editable' => false,
      'label' => '申请退款金额',
      'width' => '70',
    ),
    'refunded' =>
    array(
      'type' => 'money',
      'editable' => false,
      'label' => '已退款金额',
      'width' => '70',
    ),
    'payment' =>
    array(
      'type' => 'table:payment_cfg@ome',
      'editable' => false,
      'label' => '付款方式',
    ),
    'create_time' =>
    array(
      'type' => 'time',
      'editable' => false,
      'label' => '退款申请时间',
    ),
    'last_modified' => 
    array(
      'label' => '退款完成时间',
      'type' => 'last_modify',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
  ),
  'index' =>
  array(
    'ind_create_time' =>
    array(
      'columns' =>
      array(
        0 => 'create_time',
      ),
    ),
    'ind_return_type' =>
    array(
      'columns' =>
      array(
        0 => 'return_type',
      ),
    ),    
    'ind_last_modified' =>
    array(
      'columns' =>
      array(
        0 => 'last_modified',
      ),
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
  'comment' => '售后单据明细',
);