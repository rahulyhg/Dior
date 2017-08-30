<?php
$db['branch_product']=array (
  'columns' =>
  array (
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,

    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'store' =>
    array (
      'type' => 'number',
      'default' => 0,
      'label' => '库存',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'store_freeze' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label' => '冻结库存',
      'default' => 0,
       'in_list' => true,
      'default_in_list' => true,
    ),
    'last_modified' =>
    array (
      'type' => 'last_modify',
      'editable' => false,

    ),
    'arrive_store' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'label' => '在途库存',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'safe_store' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,

    ),
    'is_locked' =>
    array (
      'type' => 'intbool',
      'label' => '锁定安全库存',
      'editable' => false,
      'default' => '0',
    ),
    'unit_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '单位平均成本',
      'label'=>'单位平均成本',
      'required' =>true,
      //'in_list'=>true,
    ),
    'inventory_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '库存成本',
      'label'=>'库存成本',
      //'in_list'=>true,
    ),
  ),
  'index' =>
  array (
    'ind_product_id' =>
    array (
        'columns' =>
        array (
          0 => 'product_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);