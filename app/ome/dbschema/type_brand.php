<?php
$db['type_brand']=array (
  'columns' => 
  array (
    'type_id' => 
    array (
      'type' => 'table:goods_type@ome',
      'required' => true,
      'default' => 0,
      'pkey' => true,
      'editable' => false,
    ),
    'brand_id' => 
    array (
      'type' => 'table:brand@ome',
      'required' => true,
      'default' => 0,
      'pkey' => true,
      'editable' => false,
    ),
    'brand_order' => 
    array (
      'type' => 'number',
      'editable' => false,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);