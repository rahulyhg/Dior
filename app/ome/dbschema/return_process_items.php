<?php
$db['return_process_items']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      //'required' => true,
      'editable' => false,
    ),
    'reship_id' =>
    array (
      'type' => 'table:reship@ome',
     // 'required' => true,
      'editable' => false,
    ),
    'return_id' =>
    array (
      'type' => 'table:return_product@ome',
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,   
      'editable' => false,
    ),
    'bn' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'required' => true,
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'is_problem' =>
    array (
      'type' => 'bool',
      'editable' => false,
      'required' => true,
      'default' => 'false',
    ),
    'problem_type' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'op_id' =>
    array (
      'type' => 'table:account@pam',
      'editable' => false,
    ),
    'acttime' =>
    array (
      'type' => 'time',
      'editable' => false,
    ),
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
    ),
    'need_money' =>
    array (
      'type' => 'money',
      'ediatble' => false,
    ),
    'other' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'store_type' =>
    array (
      'type' => 
      array (
        0 => '新仓',
        1 => '残仓',
        2 => '报废',
      ),
      'editable' => false,
      'default' => '0',
      'required' => true,
    ),
    'is_check' =>
    array (
      'type' => 'bool',
      'editable' => false,
      'required' => true,
      'default' => 'false',
    ),
    'status' =>
    array (
      'type' => 
      array (
        0 => '默认',
        1 => '退',
        2 => '换',
        3 => '拒绝',
      ),
      'editable' => false,
      'required' => true,
      'default' => '0',
    ),
    'problem_belong' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'por_id' =>
    array (
      'type' => 'table:return_process@ome',
      'editable' => true,
    ),
    'num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 1,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);