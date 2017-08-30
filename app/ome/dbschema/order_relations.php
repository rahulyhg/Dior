<?php
$db['order_relations']=array (
  'columns' => 
  array (
    'source_order_id' => 
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'target_order_id' => 
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);