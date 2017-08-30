<?php
$db['sync_task']=array (
  'columns' => 
  array (
    'sync_task_id' => 
    array (
      'type' => 'number',
      'extra' => 'auto_increment',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'params' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'action' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
    ),
    'retry' =>
    array (
      'type' => 'number',
      'editable' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);