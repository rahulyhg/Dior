<?php
$db['supplier_goods']=array (
  'columns' => 
  array (
    'supplier_id' => 
    array (
      'type' => 'table:supplier',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'goods_id' => 
    array (
      'type' => 'table:goods@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ),
  'comment' => '供应商商品',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
