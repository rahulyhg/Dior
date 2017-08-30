<?php
$db['appropriation_items']=array (
  'columns' => 
  array (
    'item_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'appropriation_id' => 
    array (
      'type' => 'table:appropriation',
      'required' => true,
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
    ),
    'bn' => 
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'product_name' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'from_branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
    ),
    'from_pos_id' => 
    array (
      'type' => 'table:branch_pos@ome',
      'editable' => false,
    ),
    'to_branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
      'required' => true,
    ),
    'to_pos_id' => 
    array (
      'type' => 'table:branch_pos@ome',
      'editable' => false,
      'required' => true,
    ),
    'num' => 
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'from_branch_num' => 
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '调出库库存',
      'required' => true,
      'default'=>0,
    ),
    'to_branch_num' => 
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '调入库库存',
      'required' => true,
      'default'=>0,
    ),
  ), 
  'comment' => '库存调拨单明细',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
