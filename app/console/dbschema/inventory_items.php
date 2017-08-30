<?php
$db['inventory_items']=array (
  'columns' =>
  array (
    'item_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'inventory_id' =>
    array (
      'type' => 'table:inventory@console',
      'required' => true,
      'label' => '盘点单编号',
      'width' => 100,
      'in_list' => true,
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
    'quantity' =>
    array (
      'type' => 'mediumint',
      'default' => 0,
      'required' => true,
      'label' => '盘点数量',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'type' =>
    array (
      'type' => array(
        '1' => '盘盈',
        '2' => '盘亏',
        '3'=>'期初',
      ),
      'required' => true,
      'label' => '盘点盈亏',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'label' => '备注',
      'width' => 150,
      'in_list' => true,
    ),
  ),
  'comment' => '盘点申请表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);