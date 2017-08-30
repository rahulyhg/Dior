<?php
$db['goods_spec_index']=array (
  'columns' => 
  array (
    'type_id' => 
    array (
      'type' => 'table:goods_type@ome',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'spec_id' => 
    array (
      'type' => 'table:specification@ome',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'spec_value_id' => 
    array (
      'type' => 'table:spec_values@ome',
      'default' => 0,
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'goods_id' => 
    array (
      'type' => 'table:goods@ome',
      'default' => 0,
      'required' => true,
      'editable' => false,
    ),
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'default' => 0,
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ), 
  'comment' => '商品规格索引表',
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);