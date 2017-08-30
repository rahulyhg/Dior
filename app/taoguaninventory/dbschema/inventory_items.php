<?php
$db['inventory_items']=array (
  'columns' =>
   array (
    'item_id' =>
    array (
     'type' => 'number',
     'required' => true,
     'pkey' => true,
'extra' =>'auto_increment',
      'editable' => false,
    ),
 'inventory_id' =>
    array (
      'type' => 'table:inventory',
      'required' => true,
      'editable' => false,

      'label' => '盘点ID'
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'label' => '货品ID',
      'editable' => false,
    ),
    'pos_id' =>
    array (
      'type' => 'table:branch_pos@ome',
      'label' => '货位ID',
      'editable' => false,
    ),
    'name' =>
    array (
      'type' => 'varchar(100)',
      'label' => '货品名称',
      'width' => 140,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '货号',
      'width' => 140,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
     'barcode' =>
    array (
      'type' => 'varchar(32)',
      'label' => '条码',
      'width' => 140,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),

    'spec_info' =>
    array (
      'type' => 'varchar(32)',
      'label' => '规格型号',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'unit' =>
    array (
      'type' => 'varchar(10)',
      'label' => '单位',
      'width' => 30,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'pos_name' =>
    array (
      'type' => 'varchar(32)',
      'label' => '货位名称',
      'width' => 80,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'accounts_num' =>
    array (
      'type' => 'number',
      'label' => '帐面数量',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'actual_num' =>
    array (
      'type' => 'number',
      'label' => '实际数量',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'shortage_over' =>
    array (
      'type' => 'mediumint',
      'label' => '盈亏记录',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'price' =>
    array (
      'type' => 'money',
      'label' => '单价',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'availability' =>
    array(
      'type' => 'bool',
      'default' => 'false',
      'label' => '有效性',
      'editable' => false,
    ),
    'error_log' =>
    array (
      'type' => 'text',
      'editable' => false,
      'label' => '错误日志'
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
      'label' => '备注'
    ),
     'oper_time'=>array(
        'type'=>'time',
        'label'=>'操作时间'
    ),
    'is_auto'=>array(
      'type'=>array(
        0=>'否',
        1=>'是'
        ),
      'default' => '0',

      'label' => '全盘时补齐标识',

    ),
    'status'=>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'editable' => false,
      'label'=>'是否确认',
    ),
  ),
  'index' =>
  array (
    'ind_bn' =>
    array (
        'columns' =>
        array (
          0 => 'bn',
        ),
    ),
    'ind_name' =>
    array (
        'columns' =>
        array (
          0 => 'name',
        ),
    ),
    'ind_availability' =>
    array (
        'columns' =>
        array (
          0 => 'availability',
        ),
    ),
  ),
  'comment' => '盘点明细表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);