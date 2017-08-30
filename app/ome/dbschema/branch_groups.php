<?php
$db['branch_groups']=array (
  'columns' => 
  array (
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'group_id' => 
    array (
      'type' => 'table:groups@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);