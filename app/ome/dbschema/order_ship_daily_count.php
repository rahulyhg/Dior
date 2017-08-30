<?php
$db['order_ship_daily_count']=array (
  'columns' => 
  array (
    'order_date' => 
    array (
      'type' => 'varchar(8)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'in_list' => false,
      'label' => '订单日期',
      'width' => 100,
    ),
    'squence_no' =>
    array (
      'type' => 'mediumint unsigned',
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'label' => '当日唯一收货人总数',
      'width' => 100,
    ),
  ),
  'comment' => '每日订单购买人总数',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
