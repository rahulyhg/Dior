<?php
$db['product_serial_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'item_id' => 
    array (
      'type' => 'table:product_serial@ome',
      'required' => true,
      'editable' => false,
    ),
    'act_type' => 
    array (
      'type' => 'tinyint',
      'label' => '操作类型',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'act_time' => 
    array (
      'type' => 'time',
      'label' => '操作时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    'act_owner' => 
    array (
      'type' => 'table:users@desktop',
      'label' => '操作人',
      'required' => true,
      'editable' => false,
    ),
    'bill_type' => 
    array (
      'type' => 'tinyint',
      'label' => '单据类型',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'bill_no' =>
    array (
      'type' => 'varchar(30)',
      'label' => '单据编号',
      'required' => true,
      'default' => '',
      'editable' => false,
      'is_title' => true,
    ),
    'serial_status' =>
    array (
      'type' => 'tinyint',
      'default' => '0',
      'required' => true,
      'label' => '唯一码状态',
      'width' => 75,
      'editable' => false,
    ),
  ),
  'comment' => '唯一码商品日志表',
  'engine' => 'MyISAM',
  'version' => '$Rev:  $',
);