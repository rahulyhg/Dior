<?php
$db['print_tag']=array (
  'columns' =>
  array (
    'tag_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      'editable' => false,
      'is_title' => true,
      'label' => '名称',
      'width' => 260,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'order' =>'1',
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'in_list' => false,
      'label' => '创建时间',
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
      'order' =>'2',
    ),
    'last_modified' =>
    array (
      'type' => 'last_modify',
      'editable' => false,
      'label' => '最后更新时间',
     
    ),
    'intro' =>
    array (
      'type' => 'text',
      'editable' => false,
      'label' => '详细介绍',
    ),
    'config' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '配置信息',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);