<?php
$db['order_objects']=array (
  'columns' =>
  array (
    'obj_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),    'obj_type' =>
    array (
      'type' => 'varchar(50)',
      'default' => '',
      'required' => true,
      'editable' => false,
    ),
    'obj_alias' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'shop_goods_id' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'goods_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'bn' =>
    array (
      'type' => 'varchar(40)',
      'editable' => false,
      'is_title' => true,
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'price' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
    'amount' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
    'quantity' =>
    array (
      'type' => 'number',
      'default' => 1,
      'required' => true,
      'editable' => false,
    ),
    'weight' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'score' =>
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'pmt_price' =>
    array (
      'type' => 'money',
      'default' => '0',

      'editable' => false,
    ),
    'sale_price' =>
    array (
      'type' => 'money',
      'default' => '0',

      'editable' => false,
    ),
	  'pkg_name' => 
    array (
      'type' => 'varchar(250)',
      'default' => '',
      'required' => false,
      'editable' => false,
    ),
	'pkg_bn' => 
    array (
      'type' => 'varchar(50)',
      'default' => '',
      'required' => false,
      'editable' => false,
    ),
	  'pkg_id' => 
    array (
      'type' => 'varchar(50)',
      'default' => '',
      'required' => false,
      'editable' => false,
    ),
	  'pkg_price' => 
    array (
      'type' => 'money',
      'default' => '0',

      'editable' => false,
    ),
	  'pkg_num' => 
    array (
        'type' => 'number',
      'default' => 1,
      'required' => true,
      'editable' => false,
    ),
    'oid' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '子订单号',
    ),
    'is_oversold' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 0,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
);