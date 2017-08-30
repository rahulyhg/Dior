<?php
$db['inventory_apply']=array (
  'columns' =>
  array (
    'inventory_apply_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'inventory_apply_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '盘点申请单号',
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
      'in_list' => true,
      'default_in_list' => true,
      'label' => '外部仓库',
    ),
    'wms_id' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'label' => 'MWS编号',
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
    'append' =>
    array(
      'type' => 'bool',
      'default' => 'false',
      'label' => '追加',
      'width' => 50,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'status' =>
    array(
      'type' =>
      array (
        'unconfirmed' => '未确认',
        'confirmed' => '已确认',
        'closed' => '已关闭',
      ),
      'default' => 'unconfirmed',
      'required' => true,
      'label' => '状态',
      'width' => 100,
      'in_list' => true,
      'default_in_list' => true,
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
    'process_date' =>
    array (
      'type' => 'time',
      'label' => '处理时间',
      'width' => 150,
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