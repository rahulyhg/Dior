<?php
$db['return_product_problem_type']=array (
  'columns' => 
  array (
    'return_id' => 
    array (
      'type' => 'table:return_product@ome',
      'required' => true,
      'editable' => false,
    ),
    'item_id' => 
    array (
      'type' => 'table:reship_items@ome',
      'required' => true,
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'editable' => false,
    ),
    'problem_id' =>
    array (
      'type' => 'table:return_product_problem@ome',
      'required' => true,
      'editable' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);