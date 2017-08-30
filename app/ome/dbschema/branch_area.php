<?php
$db['branch_area']=array (
  'columns' =>
  array (
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'region_id' =>
    array (
      'type' => 'table:regions@eccommon',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);