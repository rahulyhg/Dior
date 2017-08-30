<?php
$db['abnormal_type']=array (
  'columns' => 
  array (
    'type_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'type_name' =>
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'default_in_list' => true,
      'in_list' => true,
      'label' => '问题名称',
      'is_title' => true,
      'searchtype' => 'has',
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
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);