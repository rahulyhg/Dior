<?php
$db['dly_items_pos']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'table:delivery_items@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'pos_id' =>
    array (
      'type' => 'table:branch_pos@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'required' => true,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);