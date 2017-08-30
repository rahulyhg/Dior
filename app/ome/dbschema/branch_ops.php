<?php
$db['branch_ops']=array (
  'columns' => 
  array (
    'branch_id' => 
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'op_id' => 
    array (
      'type' => 'table:account@pam',
      'required' => true,
      'pkey' => true,
      'editable' => false, 
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);