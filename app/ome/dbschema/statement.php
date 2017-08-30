<?php
$db['statement']=array (
  'columns' =>
  array (
    'statement_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
      'comment' => '对账单ID',
    ),
    'original_bn' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'default' => '',
      'label' => '支付单号',
      'width' => 240,
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
	  'searchtype' => 'has',
	//  'filtertype' => 'yes',
    //  'filterdefault' => true, 
      'label' => '订单号', 
      'width' => 200,
      'editable' => false,
    ),
	'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
	  'filterdefault' => true, 
    ),
    'money' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'label' => '支付金额',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'paycost' =>
    array (
      'type' => 'money',
      'label' => '支付网关费用',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'cur_money' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
      'comment' => '支付金额(RMB)',
    ),
    
    'payment' =>
    array (
      'type' => 'number',
      'editable' => false,
      'comment' => '支付方式id'
    ),
    'paymethod' =>
    array (
      'type' => 'varchar(100)',
      'label' => '支付方式',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'comment' => '备注',
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
      'comment' => '是否禁用',
    ),
    'trade_no' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'comment' => '第三方交易单号',
    ),
	'tatal_amount'=>array( 
	  'type' => 'money',
      'default' => '0',
	  'label' => '实际到账金额',
      'required' => true,
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => true,
	),
	'pay_fee'=>array(
	  'type' => 'money',
      'default' => '0',
	  'label' => '手续费',
      'required' => true,
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => true,
	),
	'fee_rate'=>array(
	  'type' => 'decimal(10,4)',
      'default' => '0',
	  'label' => '手续费率',
      'required' => true,
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => true,
	),
	'difference_money'=>array(
	  'type' => 'money',
      'default' => '0',
	  'label' => '差额',
      'required' => true,
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => true,
	),
	'original_type'=>array(
	  'type' => array(
		'payments'=>'支付对账单',
		'refunds'=>'退款对账单',
	  ),
	  'label' => '对账单类型',
      'default' => 'payments',
      'required' => true,
	  'filtertype' => 'yes',
	  'filterdefault' => true,
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => true,
	),
	'balance_status'=>array(
	  'type' => array(
		'none'=>'未对账',
		'auto'=>'自动对账完成',
		'hand'=>'手工对账完成',
		'require'=>'需要手工确认',
		'not_has'=>'未匹配到单据',
		'running'=>'同步中',
		'sync'=>'已同步',
	  ),
	  'label' => '对账状态',
      'default' => 'none',
      'required' => true,
	  'filtertype' => 'yes',
	  'filterdefault' => true,
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => true,
	),
	'cod_time'=>array(
		'type' => array(
		'first'=>'第一次导入',
		'second'=>'第二次导入',
	  ),
	  'label' => 'cod导入次数',
      'default' => 'second',
      'required' => true,
	  'filtertype' => 'yes',
	  'filterdefault' => true,
      'editable' => false,
	  'in_list' => true,
      'default_in_list' => false,
	),
	'difference_reason'=>array(
	  'type' => 'varchar(225)',
	  'label' => '差额原因',
      'editable' => false,
	  'in_list' => true,
	),
	'importer_time'=>array(
	  'type' => 'time',
	  'label' => '导入对账单时间',
      'editable' => false,
	  'in_list' => true,
	),
	'pay_time'=>array(
		'type' => 'time',
		'label' => '支付时间',
		'filtertype' => 'time',
		'filterdefault' => true,
		'editable' => false,
		'in_list' => true,
	),
    'payment_refer' =>
    array(
      'type' =>
      array(
         0=>'normal',
         1=>'aftersale',
      ),
      'default' => '0',
      'label' => '支付来源',
    ),
    'archive' =>
    array (
      'type' => 'tinyint unsigned',
      'label' => '是否归档',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '对账记录',
  'index' =>
  array (
    'ind_original_bn' =>
    array (
        'columns' =>
        array (
          0 => 'original_bn',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);