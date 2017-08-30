<?php
$db['order_objects']=array (
  'columns' =>
  array (
     'obj_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),

    'order_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),    
    'obj_type' =>
    array (
      'type' => 'varchar(50)',
      'default' => '',
      'required' => true,
      'editable' => false,
    ),
  
    'goods_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'bn' =>
    array (
      'type' => 'varchar(40)',
      'editable' => false,
      'is_title' => true,
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'price' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
    'amount' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
    'quantity' =>
    array (
      'type' => 'number',
      'default' => 1,
      'required' => true,
      'editable' => false,
    ),
  
    'pmt_price' =>
    array (
      'type' => 'money',
      'default' => '0',

      'editable' => false,
    ),
    'sale_price' =>
    array (
      'type' => 'money',
      'default' => '0',

      'editable' => false,
    ),
    'oid' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '×Ó¶©µ¥ºÅ',
    ),
  ),
  'index' =>
  array (
    'idx_c_order_id' =>
    array (
        'columns' =>
        array (
          0 => 'order_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 40912 $',
);