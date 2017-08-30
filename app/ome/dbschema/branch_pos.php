<?php
$db['branch_pos']=array (
  'columns' => 
  array (
    'pos_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'store_position' =>
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'required' => true,
      'label' => '货位',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'editable' => false,
      'label' => '仓库',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'nequal',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'stock_threshold' =>
    array (
      'type' => 'number',
      'required' => false,
      'default' => 0,
      'editable' => false,
    ),
  ),
  'index' =>
  array (
    'ind_branch_pos' =>
    array (
      'columns' =>
      array (
        0 => 'branch_id',
        1 => 'store_position'
      ),
      'prefix' => 'unique',
    ),
    'ind_store_position' =>
    array (
      'columns' =>
      array (
        0 => 'store_position',
      ),
    ),
    'ind_pos_id' =>
    array (
        'columns' =>
        array (
          0 => 'pos_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);