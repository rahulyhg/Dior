<?php
$db['sendlist']=array (
  'columns' =>
  array (
    'send_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => '自增ID',
      'width' => 150,
      'comment' => '自增ID',
      'editable' => false,
    ),
    'send_bn' =>
    array (
      'type' => 'varchar(50)',
      'label' => '发送编码',
      'width' => 160,
      'is_title' => true,
      'required' => true,
      'comment' => '发送编码',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
      'in_list' => true,
      'default_in_list' => true,
     ),
     'send_name' =>
    array (
      'type' => 'varchar(100)',
      'label' => '模板名称',
      'width' => 150,
      'comment' => '模板名称',
      'editable' => false,
      'searchtype' => 'has',
      'in_list' => true,
       'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
    ),
    'senders' =>
    array (
      'type' => 'text',
      'label' => '发送目标',
      'width' => 150,
      'comment' => '发送目标',
      'editable' => false,
      'searchtype' => 'has',
      'in_list' => true,
       'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
    ),
    'send_tmpl' =>
    array (
      'type' => 'varchar(255)',
      'label' => '模板',
      'width' => 350,
      'comment' => '模板',
      'editable' => false,
      'searchtype' => 'has',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'send_desc' =>
    array (
      'type' => 'varchar(255)',
      'comment' => '简介',
      'editable' => false,
      'label' => '简介',
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '发送列表',
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);