<?php
$db['eo_items']=array (
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
    'eo_id' =>
    array (
      'type' => 'table:eo',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '入库单编号',
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
    ),
    'product_name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '货品名称',
    ),
    'bn' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '货号',
    ),
    'unit' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '单位',
    ),
    'purchase_num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '采购数量',
    ),
    'entry_num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '入库数量',
    ),
    'is_new' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
      'in_list' => true,
      'label' => '是否新品',
    ),
    'pos_id' =>
    array (
      'type' => 'table:branch_pos@ome',
      'required' => true,
      'editable' => false,
      'default' => '0',
    ),
    'out_num' =>
    array (
      'type' => 'int',
      'editable' => false,
      'default_in_list' => true,
      'default' => '0',
      'label' => '退货数量',

    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
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
    'defective_num' =>
    array (
      'type' => 'number',
      'label' => '不良品数量',
	  'default' => 0,
    ),
  ),
  'comment' => '入库单明细',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
