<?php
$db['supplier_brand']=array (
  'columns' => 
  array (
    'supplier_id' => 
    array (
      'type' => 'table:supplier',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'brand_id' => 
    array (
      'type' => 'table:brand@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ),
  'comment' => '供应商提供的品牌',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
