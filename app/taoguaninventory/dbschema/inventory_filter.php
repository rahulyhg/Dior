<?php
$db['inventory_filter']=array (
  'columns' => 
  array (
    'filter_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'name' => 
    array (
      'type' => 'varchar(100)',
      'label' => '筛选条件名称',
      'editable' => false,
    ),
    'filter' => 
    array (
      'type' => 'text',
      'label' => '筛选条件',
      'required' => true,
      'editable' => false,
    ),
  ),
  'comment' => '盘点筛选条件表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);