<?php
$db['inventory_apply_items']=array (
  'columns' =>
  array (
    'item_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'inventory_apply_id' =>
    array (
      'type' => 'table:inventory_apply@console',
      'required' => true,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
    ),
    'bn' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => '货号',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'name' =>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'label' => '货品名称',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'normal_num' =>
    array (
      'type' => 'mediumint',
      'default' => 0,
      'required' => true,
      'label' => '良品',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'defective_num' =>
    array (
      'type' => 'mediumint',
      'default' => 0,
      'required' => true,
      'label' => '不良品',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'quantity' =>
    array (
      'type' => 'mediumint',
      'default' => 0,
      'required' => true,
      'label' => '盘点数量',
      'in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'label' => '备注',
      'in_list' => true,
    ),
  ),
  'comment' => '盘点申请表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);