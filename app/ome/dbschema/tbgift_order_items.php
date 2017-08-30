<?php
$db['tbgift_order_items']=array (
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
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'outer_item_id' =>
    array (
      'type' => 'bigint(20)',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'label' => '赠品名称',
      'editable' => false,
    ),
    'nums' =>
    array (
      'type' => 'number',
      'default' => 1,
      'required' => true,
      'editable' => false,
    ),
  ),
  'comment' => '淘宝订单赠品信息表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
