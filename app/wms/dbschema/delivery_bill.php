<?php
$db['delivery_bill']=array (
  'columns' =>
  array (
    'b_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'delivery_id' =>
    array (
      'type' => 'table:delivery@wms',
      'required' => true,
      'editable' => false,
      'label' => '发货单号',
      'comment' => '配送流水号',
      'width' =>140,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'logi_no' =>
    array (
      'type' => 'varchar(50)',
      'label' => '物流单号',
      'comment' => '物流单号',
      'editable' => false,
      'width' =>110,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'type' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 1,
      'width' => 150,
      'required' => true,
      'comment' => '物流单主次之分',   
      'editable' => false,
    ),
    'print_status' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 0,
      'width' => 150,
      'required' => true,
      'comment' => '打印状态',
      'editable' => false,
      'label' => '打印状态',
    ),
    'status' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 0,
      'width' => 150,
      'required' => true,
      'comment' => '处理状态',
      'editable' => false,
      'label' => '处理状态',
    ),
    'net_weight' =>
    array (
      'type' => 'money',
      'editable' => false,
      'comment' => '商品重量',
    ),
    'weight' =>
    array (
      'type' => 'money',
      'editable' => false,
      'label' => '包裹重量',
      'comment' => '包裹重量',
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'delivery_cost_expect' =>
    array (
      'type' => 'money',
      'default' => '0',
      'editable' => false,
      'width' =>75,
      'label' => '预计物流费用',
      'comment' => '预计物流费用(包裹重量计算的费用)',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'delivery_cost_actual' =>
    array (
      'type' => 'money',
      'default' => '0',
      'editable' => false,
      'width' =>75,
      'label' => '实际物流费用',
      'comment' => '实际物流费用(物流公司提供费用)',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'label' => '创建时间',
      'comment' => '单据生成时间',
      'editable' => false,
      'filtertype' => 'time',
      'width' =>110,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'delivery_time' =>
    array (
      'type' => 'time',
      'label' => '发货时间',
      'comment' => '单据发货时间',
      'editable' => false,
      'filtertype' => 'time',
      'width' =>110,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'index' =>
  array (
    'index_logi_no' =>
    array (
      'columns' =>
      array (
        0 => 'logi_no',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);