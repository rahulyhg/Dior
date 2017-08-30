<?php
$db['autoconfirm']=array (
  'columns' => 
  array (
    'oid' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'editable' => false,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 130,
      'label' => '规则名称',
    ),
    'config' =>
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
    ),
    'defaulted' => 
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
    ),
  ),
  
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);