<?php

$db['delivery_items']=array (
 'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),

    'delivery_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
  
    'bn' => 
    array (
      'type' => 'varchar(30)',
      'editable' => false,
      'is_title' => true,
    ),
    'product_name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'number' => 
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
//    'verify' => 
//    array (
//      'type' => 'bool',
//      'required' => true,
//      'default' => 'false',
//      'editable' => false,
//    ),
//    'verify_num' => 
//    array (
//      'type' => 'number',
//      'required' => true,
//      'default' => 0,
//      'editable' => false,
//    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);