<?php
$db['shop_groups']=array (
  'columns' => 
  array (
    'shop_id' => 
    array (
      'type' => 'table:shop@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'group_id' =>
    array (
      'type' => 'table:groups@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);