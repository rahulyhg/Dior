<?php
$db['queue']=array (
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
    'order_bn' =>
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'default' => '',
    ),
	'status' =>
    array (
      'type' =>
      array (
        '0' => '未执行',
        '1' => '执行中',
		'2' => '已执行',
      ),
      'default' => '0',
      'required' => true,
      'label' => '状态',
    ),
	 'queue_type' =>
    array (
     'type' =>
      array (
        'statement' => '对账',
      ),
      'default' => 'statement',
      'required' => true,
      'label' => '类型',
    ),
	'createtime' =>
    array (
      'type' => 'time',
      'required' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);