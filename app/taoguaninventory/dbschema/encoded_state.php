<?php
$db['encoded_state']=array (
  'columns' =>
  array (
    'eid' =>
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
      'label' => '标识',
      'editable' => true,
       'in_list' => true,
      'default_in_list' => true,
    ),
    'head' =>
    array (
      'type' => 'text',
      'label' => '前缀',
      'required' => true,
      'editable' => false,
       'in_list' => true,
      'default_in_list' => true,
    ),
    'currentno' =>
    array (
      'type' => 'number',
      'label' => '当前编号',
      'required' => true,
      'editable' => false,
      'default' => 0,
       'in_list' => true,
      'default_in_list' => true,
    ),
     'bhlen' =>
    array (
      'type' => 'number',
      'label' => '编码长度',
      'required' => true,
      'editable' => false,
       'default' => 4,
       'in_list' => true,
      'default_in_list' => true,
    ),
     'description' =>
    array (
      'type' => 'varchar(40)',
      'label' => '用途',

      'editable' => false,
       'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '编码状态表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);