<?php
$db['operations']=array (
  'columns' => 
  array (
    'operation_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'operation_name' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'editable' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);