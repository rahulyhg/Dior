<?php
$db['orders']=array (
  'columns' => 
  array (
     'id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'OrderId' =>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
    ),
	'status' =>
    array (
      'type' =>
      array (
        '0' => '未获取',
        '1' => '已获取',
      ),
      'default' => '0',
      'required' => true,
      'label' => '状态',
    ),
	 'PageId' =>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
    ),
	 'Event' =>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
    ),
	 'MsgType' =>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
    ),
	 'CreateTime' =>
    array (
      'type' => 'time',
      'required' => false,
    ),
	'ToUserName' =>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
    ),
	'FromUserName' =>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);