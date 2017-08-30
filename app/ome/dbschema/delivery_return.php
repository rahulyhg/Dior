<?php
$db['delivery_return']=array (
  'columns' => 
  array (
    'return_id' => 
    array (
      'type' => 'table:return_product@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'delivery_id' => 
    array (
      'type' => 'table:delivery@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);