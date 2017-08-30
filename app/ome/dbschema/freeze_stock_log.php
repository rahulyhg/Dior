<?php
$db['freeze_stock_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'log_type' =>
    array (
      'type' => 'varchar(30)',
      'label' => '日志类型',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'mark_no' => 
    array (
      'type' => 'varchar(15)',
      'required' => true,
      'editable' => false,
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'shop_type' =>
    array (
      'type' => 'varchar(50)',
      'label' => '店铺类型',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
	'order_bn' => 
    array (
      'type' => 'varchar(32)',
      'label' => '订单号',
      //'required' => true,
      'editable' => false,
    ),
    'delivery_bn' => 
    array (
      'type' => 'varchar(32)',
      'label' => '发货单号',
      //'required' => true,
      'editable' => false,
    ),
    'oper_id' =>
	array(
	  'type' => 'varchar(100)',
      'label' => '操作员ID',
      'default' => 0,
      'editable' => false,
	),
	'oper_name' =>
	array(
	  'type' => 'varchar(100)',
      'label' => '操作员',
      //'required' => true,
      'editable' => false,
	),
    'oper_time' =>
    array (
      'type' => 'time',
      'label' => '操作时间',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'trigger_object_type' =>
    array (
      'type' => 'varchar(255)',
      'label' => '触发对象类型',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'trigger_action_type' =>
    array (
      'type' => 'varchar(100)',
      'label' => '触发动作类型',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
    ),
    'branch_name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 130,
      'label' => '仓库名',
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'label' => '货品ID',
      'width' => 110,
      'editable' => false,
    ),
    'goods_id' => 
    array (
      'type' => 'table:goods@ome',
      'default' => 0,
      //'required' => true,
      'label' => '商品ID',
      'width' => 110,
      'editable' => false,
    ),
    'bn' => 
    array (
      'type' => 'varchar(30)',
      'label' => '货号',
      'editable' => false,
      'is_title' => true,
    ),
    'stock_action_type' =>
    array (
      'type' => 'varchar(30)',
      'label' => '库存调整类型',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'last_num' =>
    array(
        'type' => 'number',
        //'required' => true,
        'label' => '原预占数',
        'editable' => false,
        'in_list' => false,
        'default' => 0,
    ),
    'change_num' =>
    array(
        'type' => 'number',
        //'required' => true,
        'label' => '预占调整数',
        'editable' => false,
        'in_list' => false,
        'default' => 0,
    ),
    'current_num' =>
    array(
        'type' => 'number',
        //'required' => true,
        'label' => '调整后预占数',
        'editable' => false,
        'in_list' => false,
        'default' => 0,
    ),
  ),
  'comment' => '冻结库存流水表',
  'index' =>
  array (
    'ind_bn' =>
    array (
      'columns' =>
      array (
        0 => 'bn',
      ),
    ),
    'ind_oper_time' =>
    array (
      'columns' =>
      array (
        0 => 'oper_time',
      ),
    ),
    'ind_log_type' =>
    array (
      'columns' =>
      array (
        0 => 'log_type',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);