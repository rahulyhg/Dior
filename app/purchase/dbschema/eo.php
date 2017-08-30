<?php
$db['eo']=array (
  'columns' =>
  array (
    'eo_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'supplier_id' =>
    array (
      'type' => 'table:supplier',
      'label' => '供应商',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filterdefault' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'po_id' =>
    array (
      'type' => 'table:po',
      'required' => true,
      'label' => '采购单编号',
      'editable' => false,
     // 'in_list' => true,
      //'default_in_list' => true,
    ),
    'eo_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '入库单编号',
      'width' => 150,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
      'is_title' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
    ),
    'amount' =>
    array (
      'type' => 'money',
      'label' => '金额',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filterdefault' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'entry_time' =>
    array (
      'type' => 'time',
      'label' => '入库时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
       'filterdefault' => true,
      'searchtype' => 'has',
      'filtertype' => 'time',
    ),
    'arrive_time' =>
    array (
      'type' => 'time',
      'label' => '到货日期',
      'width' => 80,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
    ),
    'operator' =>
    array (
      'type' => 'varchar(50)',
      'label' => '经办人',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'editable' => false,
    ),
    'status' =>
    array (
      'type' =>
      array (
        1 => '已入库',
        2 => '部分退货',
        3 => '已退货',
      ),
      'default' => 1,
      'label' => '入库状态',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
    ),
  ),
  'index' =>
  array (
    'ind_status' =>
    array (
      'columns' =>
      array (
        0 => 'status',
      ),
    ),
    'ind_eo_bn' =>
    array (
      'columns' =>
      array (
        0 => 'eo_bn',
      ),
    ),
  ),
  'comment' => '入库单( entry_order )',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
