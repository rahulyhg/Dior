<?php
$db['payments']=array (
  'columns' =>
  array (
    'payment_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
      'comment' => '支付单ID',
    ),
    'payment_bn' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'default' => '',
      'label' => '支付单号',
      'width' => 240,
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'label' => '订单号',
      'width' => 200,
      'editable' => false,
//      'in_list' => true,
//      'default_in_list' => true,
    ),
    'account' =>
    array (
      'type' => 'varchar(50)',
      'label' => '收款账号',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'bank' =>
    array (
      'type' => 'varchar(50)',
      'label' => '收款银行',
      'width' => 80,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'pay_account' =>
    array (
      'type' => 'varchar(50)',
      'label' => '支付账户',
      'width' => 130,
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'currency' =>
    array (
      'type' => 'varchar(10)',
      'label' => '货币',
      'width' => 65,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
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
    'pay_type' =>
    array (
      'type' =>
      array (
        'online' => '在线支付',
        'offline' => '线下支付',
        'deposit' => '预存款支付',
      ),
      'default' => 'online',
      'required' => true,
      'label' => '支付类型',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
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
    'op_id' =>
    array (
      'type' => 'table:account@pam',
      'label' => '操作员',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'op_name' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'ip' =>
    array (
      'type' => 'ipaddr',
      'label' => '支付IP',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    't_begin' =>
    array (
      'type' => 'time',
      'label' => '支付开始时间',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
    't_end' =>
    array (
      'type' => 'time',
      'label' => '支付完成时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    'download_time' =>
    array (
      'type' => 'time',
      'label' => '单据下载时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    'status' =>
    array (
      'type' =>
      array (
        'succ' => '支付成功',
        'failed' => '支付失败',
        'cancel' => '未支付',
        'error' => '处理异常',
        'invalid' => '非法参数',
        'progress' => '处理中',
        'timeout' => '超时',
        'ready' => '准备中',
      ),
      'default' => 'ready',
      'required' => true,
      'label' => '支付状态',
      'width' => 75,
      'editable' => false,
      'filtertype' => 'yes',
      'hidden' => true,
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
	'tatal_amount'=>array( 
	  'type' => 'money',
      'default' => '0',
	  'label' => '实际到账金额',
      'required' => true,
      'editable' => false,
	),
	'pay_fee'=>array(
	  'type' => 'money',
      'default' => '0',
	  'label' => '手续费',
      'required' => true,
      'editable' => false,
	),
	'statement_status'=>array(
	  'type' => 'bool',
	  'label' => '是否已生成对账单',
      'default' => 'false',
      'editable' => false,
	),
	'fee_rate'=>array(
	  'type' => 'decimal(10,4)',
      'default' => '0',
	  'label' => '手续费率',
      'required' => true,
      'editable' => false,
	),
	'difference_money'=>array(
	  'type' => 'money',
      'default' => '0',
	  'label' => '差额',
      'required' => true,
      'editable' => false,
	),
	'balance_status'=>array(
	  'type' => array(
		'none'=>'未对账',
		'auto'=>'自动对账完成',
		'hand'=>'手工对账完成',
		'require'=>'需要手工确认',
		'sync'=>'已同步',
	  ),
	  'label' => '对账状态',
      'default' => 'none',
      'required' => true,
      'editable' => false,
	),
	'difference_reason'=>array(
	  'type' => 'varchar(225)',
	  'label' => '差额原因',
      'editable' => false,
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
  'comment' => '支付记录',
  'index' =>
  array (
    'ind_payment_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'payment_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_payment_bn' =>
    array (
        'columns' =>
        array (
          0 => 'payment_bn',
        ),
    ),
    'ind_t_end' =>
    array (
        'columns' =>
        array (
          0 => 't_end',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);