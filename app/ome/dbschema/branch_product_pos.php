<?php
$db['branch_product_pos']=array (
  'columns' =>
  array (
    'pp_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
     // 'pkey' => true,
    ),
    'pos_id' =>
    array (
      'type' => 'table:branch_pos@ome',
      'required' => true,
     // 'pkey' => true,
      'editable' => false,
      'label' => '货位',
   	  'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'editable' => false,
    ),
    'store' =>
    array (
      'type' => 'mediumint',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => '库存',
      'in_list' => false,
      'default_in_list' => false,
    ),
    'default_pos' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'create_time' =>
    array (
      'label' => '更新时间',
      'type' => 'time',
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'operator' =>
    array (
      'type' => 'varchar(50)',
      'label' => '操作人',
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);