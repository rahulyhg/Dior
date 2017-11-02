<?php
$db['invoice']=array (
  'columns' =>
  array (
	'id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ), 
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'editable' => false,
    ),
    'order_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '订单号',
      'width' => 125,
      'searchtype' => 'nequal',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => false,
    ),
	'taxIdentity'=>array(
	  'type' => 'varchar(100)',
	  'label' => '税号',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
	),
    'bankFullName' =>
    array (
      'type' => 'varchar(100)',
	  'label' => '开户行名称',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
    'bankAccount' =>
    array (
      'type' => 'varchar(100)',
	  'label' => '银行账户',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
    'address' =>
    array (
      'type' => 'varchar(100)',
	  'label' => '地址',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
	 'phone' =>    
    array (
      'type' => 'varchar(100)',
	  'label' => '电话',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
	'invoice_type' =>
    array (
      'type' => array(
		 'ready'=>'尚未开票',
		 'active'=>'已开票',
		 'cancel'=>'已作废',
	  ),
	  'label' => '发票状态',
	  'width' => 125,
	  'default'=>'ready',
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
	'invoice_id' =>
    array (
      'type' => 'varchar(100)',
	  'label' => '发票id',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
	'pdfUrl' =>
    array (
      'type' => 'varchar(255)',
	  'label' => 'PDF下载地址',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
	'invoiceCode' =>
    array (
     'type' => 'varchar(100)',
	  'label' => '发票代码',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
	'invoiceNo' =>
    array (
     'type' => 'varchar(100)',
	  'label' => '发票号码',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),

	'invoiceTime' =>
    array (
      'type' => 'varchar(100)',
	  'label' => '开票时间',
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);