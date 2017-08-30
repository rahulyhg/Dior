<?php
$db['operations_order']=array (
  'columns' => 
  array (
    'operation_id' => 
    array (
      'type' => 'int unsigned',
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
	'order_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
    ),
	'order_detail' =>
	array(
	  'type' => 'longtext',
      'required' => true,
      'editable' => false,
	),
  ),
  'index' => array(
    'idx_log_id' => array('columns' => array('log_id')),
    'idx_order_id' => array('columns' => array('order_id')),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);