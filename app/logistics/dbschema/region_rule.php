<?php
$db['region_rule']=array (
  'columns' =>
  array (
  'id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),

    'item_id' =>
    array (
      'type' => 'int',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),

    'region_id' =>
    array (
      'type' => 'int',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'region_grade' =>
        array(
            'type' => 'number',
            'editable' => false,
        ),

 'obj_id' =>
    array (
      'type' => 'int',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),




),
 'index' =>array (
 'ind_region_id' =>
    array (
      'columns' =>
      array (
        0 => 'region_id',
      ),
    ),

 ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);