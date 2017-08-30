<?php
$db['abnormal']=array (
  'columns' => 
  array (
    'abnormal_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'abnormal_memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'abnormal_type_id' => 
    array (
      'type' => 'table:abnormal_type@ome',
      'editable' => false,
    ),
   'abnormal_type_name' =>
    array (
      'type' => 'varchar(100)',
      'label' => '异常类型',
      'default' => '',
      'default_in_list' => true,
      'in_list' => true,
       'is_title' => true,
      'editable' => false,
      'width' => 75,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'order_id' => 
    array (
      'type' => 'table:orders@ome',
      'editable' => false,
    ),
    'is_done' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);