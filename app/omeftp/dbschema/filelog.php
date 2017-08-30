<?php
$db['filelog']=array (
  'columns' =>
  array (
    'file_log_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'content' =>
    array (
      'type' => 'text',
      'required' => true,
	  'default'=>'',
      'label' => '读写内容',
    ),
    'io_type' =>
    array (
      'type' => array(
		 'in'=>'写入',
		 'out'=>'读出',
	  ),
      'required' => true,
	  'default'=>'in',
      'in_list' => true,
      'default_in_list' => true,
      'label' => '读写',
    ),
	'work_type'=>array(
		'type' =>'varchar(50)',
	    'required' => true,
	    'in_list' => true,
	    'default_in_list' => true,
	    'label' => '业务类型',
	),
    'createtime' =>
    array (
      'type' => 'time',
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '读写时间',
    ),
    'lastmodify' =>
    array (
      'type' => 'time',
      'in_list' => true,
      'label' => '最后重试时间',
    ),
    'status' =>
    array (
      'type' => array(
		'prepare'=>'准备',
		'succ'=>'成功',
		'fail'=>'失败',
	  ),
      'label' => '状态',
      'width' => 150,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'file_route' =>
    array (
      'type' => 'varchar(225)',
      'label' => '操作文件',
      'width' => 100,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'memo' =>
    array (
      'type' => 'varchar(255)',
      'label' => '备注',
      'width' => 150,
      'in_list' => true,
    ),
  ),
  'comment' => '文件读写日志表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);