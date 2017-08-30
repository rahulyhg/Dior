<?php
$db['inventory']=array (
  'columns' =>
  array (
    'inventory_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'inventory_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '盘点单号',
      'width' => 140,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'out_id' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '外部仓库',
    ),
    'branch_bn' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '内部仓库',
    ),
    'inventory_apply_id' =>
    array (
      'type' => 'table:inventory_apply@console',
      'required' => true,
      'in_list' => true,
      'label' => '盘点申请单号',
    ),
    'type' =>
    array(
      'type' =>
      array (
        'once' => '单次',
        'many' => '多次',
      ),
      'default' => 'once',
      'required' => true,
      'label' => '盘点生成类型',
      'width' => 120,
      'in_list' => true,
    ),
    'inventory_date' =>
    array (
      'type' => 'time',
      'label' => '盘点日期',
      'width' => 150,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'create_date' =>
    array (
      'type' => 'time',
      'label' => '生成时间',
      'width' => 100,
      'in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
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