<?php
$db['order_pmt']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'editable' => false,
    ),
    'pmt_amount' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'pmt_memo' =>
    array (
      'type' => 'longtext',
      'edtiable' => false,
    ),
    'pmt_describe' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);