<?php
$db['stock_change_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'log_bn' => 
    array (
      'type' => 'varchar(12)',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '日志编号',
    ),
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 100,
      'label' => '仓库编号',
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 100,
      'label' => '商品ID',
    ),
    'bn' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 100,
      'label' => '商品货号',
    ),
    'product_name' =>
    array(
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'create_time' => 
    array (
      'type' => 'time',
      'label' => '创建时间',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'last_modified' =>
    array (
      'label' => '最后更新时间',
      'type' => 'last_modify',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'store' =>
    array (
      'label' => '变更数量',
      'type' => 'number',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'type' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'default' => 'sell',
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'label' => '变更类型',
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
  ),
  'index' =>
  array (
    'ind_log_bn' =>
    array (
        'columns' =>
        array (
          0 => 'log_bn',
        ),
    ),
    'ind_type' =>
    array (
        'columns' =>
        array (
          0 => 'type',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
