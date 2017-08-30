<?php
$db['api_fail']=array (
  'columns' => 
  array (
    'order_id' => 
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => '订单ID',
    ),
    'type' =>
    array (
      'type' => 
      array (
        'payment' => '支付',
        'refund' => '退款',
      ),
      'required' => true,
      'default' => 'payment',
      'label' => '请求类型'
    ),
  ),
  'index' =>
  array (
    'ind_orderid_type' =>
    array (
        'columns' =>
        array (
          0 => 'order_id',
          1 => 'type'
        ),
        'prefix' => 'unique',
    ),
  ),
  'comment' => '请求失败记录',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
