<?php
$db['dly_corp_area']=array (
  'columns' =>
  array (
    'corp_id' =>
    array (
      'type' => 'table:dly_corp@ome',
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