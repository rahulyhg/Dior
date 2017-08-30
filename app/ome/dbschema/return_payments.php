<?php
$db['return_payments']=array (
  'columns' => 
  array (
    'payment_id' => 
    array (
      'type' => 'table:payments@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'comment' => '支付单ID',
    ),
    'return_id' => 
    array (
      'type' => 'table:return_product@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'comment' => '售后ID',      
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment' => '售后单与[退货]支付单(明细)对应表',
);