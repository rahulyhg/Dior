<?php

$db['tbjz_orders']=array (
  'columns' =>
  array (
   'order_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'cid' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
     'oid' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '子订单号',
    ),
  ),
  'index' =>
  array (
    'ind_orderid_type' =>
    array (
        'columns' =>
        array (
          0 => 'order_id',
          ),
        'prefix' => 'unique',
    ),
  ),
  'comment' => '淘宝家装订单附加信息表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
