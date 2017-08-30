<?php
$db['po_items']=array (
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

    'po_id' =>
    array (
      'type' => 'table:po',
      'required' => true,
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
    ),
    'num' =>
    array (
      'type' => 'number',
      'required' => true,
      'editable' => false,
    ),
    'in_num' =>
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'out_num' =>
    array (
      'type' => 'number',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'price' =>
    array (
      'type' => 'money',
      'required' => true,
      'editable' => false,
    ),
    'status' =>
    array (
      'type' =>
      array (
        1 => '待入库',
        2 => '部分入库',
        3 => '完成入库',
      ),
      'label' => '采购详情状态',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'barcode' =>
    array (
      'type' => 'varchar(32)',
      'label' => '条形码',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'bn' =>
    array (
      'type' => 'varchar(30)',
      'label' => '货号',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'label' => '货品名称',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'spec_info' =>
    array (
      'type' => 'longtext',
      'label' => '货品描述',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
     'defective_num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'required' => true,
      'default' => 0,
      'label' => '不良品',
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
  ),
  'comment' => '采购单明细',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
