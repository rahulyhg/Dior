<?php
$db['errororders']=array (
  'columns' => 
  array (
    'err_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => '',
      'label' => '订单号',
      'width' => 140,
      'editable' => false,
	  'searchtype' => 'has',
      'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
    ),
	'err_msg' =>
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      'label' => '错误信息',
      'width' => 140,
      'editable' => false,
      'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
    ),
	'apitime' =>
    array (
      'type' => 'time',
      'label' => '调用时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
	  'default_in_list' => true,
    ),
	'params' => 
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '接口参数',
	  'in_list' => true,
	  'default_in_list' => true,
      //'filtertype' => 'yes',
	  //'filterdefault' => true,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);