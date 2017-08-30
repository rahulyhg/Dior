<?php
$db['delivery_order']=array (
  'columns' => 
  array (
    'order_id' => 
    array (
     'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
  
    ),
    'delivery_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ), 
  'index' =>
  array (
    'ind_delivery_id' =>
    array (
        'columns' =>
        array (
          0 => 'delivery_id',
        ),
    ),
    'ind_order_id' =>
    array (
        'columns' =>
        array (
          0 => 'order_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);