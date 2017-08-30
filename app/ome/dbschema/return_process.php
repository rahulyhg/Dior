<?php
$db['return_process']=array (
  'columns' => 
  array (
    'por_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'reship_id' =>
    array (
      'type' => 'table:reship@ome',
     // 'required' => true,
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
     // 'required' => true,
      'editable' => false,
    ),
    'return_id' =>
    array (
      'type' => 'table:return_product@ome',
      //'required' => true,
      'editable' => false,
    ),
    'member_id' =>
    array (
      'type' => 'table:members@ome',
      'editable' => false,
    ),
    'title' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
       'label' => '售后服务标题',
         'in_list' => true,
       'default_in_list' => true,
    ),
    'content' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'add_time' =>
    array (
      'type' => 'time',
      'editable' => false,
       'label' => '售后处理时间',
         'in_list' => true,
       'default_in_list' => true,
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'editable' => false,
    ),
    'last_modified' => 
    array (
      'type' => 'last_modify',
      'editable' => false,
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
         'in_list' => true,
      'default_in_list' => true,
      'label' => '仓库',
    ),
    'attachment' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'comment' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'process_data' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'recieved' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
    ),
    'verify' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
    ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);