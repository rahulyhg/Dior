<?php
$db['operation_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
  'op_id' => 
    array (
      'type' => 'table:account@pam',
      'editable' => false,
      'required' => true,
    ),
    'op_name' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
       'in_list' => true,
        'default_in_list' => true,
        'label' => '操作人',
    ),
    'operate_time' => 
    array (
      'type' => 'time',
      'required' => true,
      'editable' => false,
       'in_list' => true,
        'default_in_list' => true,
         'label' => '时间',
    ),
    'archive_time' => 
    array (
    'type' => 'time',
    'editable' => false,
    'label' => '归档时间',
     'in_list' => true,
     'default_in_list' => true,
    ),
    'memo' => 
    array (
      'type' => 'text',
      'editable' => false,
       'in_list' => true,
        'default_in_list' => true,
         'label' => '内容',
    ),
    'ip' => 
    array (
      'type' => 'varchar(15)',
      'editable' => false,
       'in_list' => true,
        'default_in_list' => true,
         'label' => 'IP',
    ),
  ),

  'engine' => 'innodb',
  'version' => '$Rev:  $',
);