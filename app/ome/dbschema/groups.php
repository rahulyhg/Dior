<?php
$db['groups']=array (
  'columns' => 
  array (
    'group_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'name' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'is_title' => true,
      'label' => '名称',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'config' => 
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'description' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'g_type' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
      'required' => true,
      'default' => 'confirm',
      'label' => '所属版块',
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
      'required' => true,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);