<?php
$db['dailystock']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
  'stock_date' => 
    array (
      'type' => 'varchar(15)',
      'required' => true,
    'label' => '记录日期',
      'editable' => false,
    ),
  'branch_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'product_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'product_bn' => 
    array (
      'type' => 'varchar(30)',
      'label' => '货品编码',
      'width' => 85,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'stock_num' => 
    array (
      'type' => 'mediumint',
     'label' => '库存数量',
      'default' => 0,
      'editable' => false,
    ),
    'unit_cost' =>
    array(
       'type' => 'money',
     'label' => '单位成本',
       'default' => 0,
    ),
  'inventory_cost' =>
    array(
       'type' => 'money',
     'label' => '库存成本',
       'default' => 0,
    ),
  'is_change' =>
    array(
       'type' => 'tinyint(1)',
     'label' => '较上次是否改变',
       'default' => 0,
    ),
  ),
  'index' =>
  array (
    'ind_product_id' =>
    array (
        'columns' =>
        array (
          0 => 'product_id',
        ),
    ),
  'ind_stock_date' =>
    array (
        'columns' =>
        array (
          0 => 'stock_date',
        ),
    ),
  'ind_branch_id' =>
    array (
        'columns' =>
        array (
          0 => 'branch_id',
        ),
    ),
    'ind_date_branch_product' =>
    array(
      'columns' => array('stock_date','branch_id','product_id'),
      'prefix' => 'unique',
    )
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
  'comment' => app::get('ome')->_('每日期初数据表'),
);