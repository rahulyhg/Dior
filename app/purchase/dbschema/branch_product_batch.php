<?php
$db['branch_product_batch']=array (
  'columns' => 
  array (
    'product_id' => 
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'supplier_id' => 
    array (
      'type' => 'table:supplier',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'eo_id' => 
    array (
      'type' => 'table:eo',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'eo_bn' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'editable' => false,
    ),
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'editable' => false,
    ),
    'purchase_price' => 
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'purchase_time' => 
    array (
      'type' => 'time',
      'editable' => false,
    ),
    'store' => 
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'in_num' => 
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'out_num' => 
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
    ),
  ),
  'comment' => '货品价格历史记录',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
