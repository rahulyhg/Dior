<?php
$db['product_serial']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => '仓库',
      'width' => 110,
       'in_list' => true,
      'default_in_list' => false,
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'bn' => 
    array (
      'type' => 'varchar(30)',
      'required' => true,
      'default' => '',
      'editable' => false,
      'label' => '货号',
      'width' => 85,
       'in_list' => true,
      'default_in_list' => false,
       'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'serial_number' => 
    array (
      'type' => 'varchar(30)',
      'required' => true,
      'default' => '',
      'editable' => false,
      'label' => '唯一码',
      'width' => 85,
      'is_title' => true,
       'in_list' => true,
      'default_in_list' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'status' =>
    array (
      'type' => array (
        '0' => '入库',
        '1' => '出库',
        '2' => '无效',
       ),
      'default' => '0',
      'required' => true,
      'label' => '状态',
      'width' => 75,
      'editable' => false,
       'in_list' => true,
      'default_in_list' => false,
       'filtertype' => 'yes',
      'filterdefault' => true,
    ),
  ),
  'comment' => '商品唯一码表',
  'index' =>
  array (
    'uni_serial_number' =>
    array (
      'columns' =>
      array (
        0 => 'serial_number',
      ),
    ),
  ), 
  'engine' => 'MyISAM',
  'version' => '$Rev:  $',
);