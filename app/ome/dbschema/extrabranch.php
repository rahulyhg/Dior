<?php
$db['extrabranch']=array (
  'columns' => 
  array (
    'branch_id' => 
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
      'label' => '仓库名',
    ),
    'address' => 
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'label' => '地址',
      'in_list' => true,
    ),
    'zip' => 
    array (
      'type' => 'varchar(20)',
      'editable' => false,
      'label' => '邮编',
      'in_list' => true,
    ),
	'email' => 
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => 'Email',
      'in_list' => true,
    ),
    'phone' => 
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'label' => '电话',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'uname' => 
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'label' => '联系人姓名',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'mobile' => 
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'label' => '手机',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'area' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
  ),
  
  'index' =>
  array (
    'name' =>
    array (
        'columns' =>
        array (
          0 => 'name',
        ),
        'prefix' => 'unique',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);