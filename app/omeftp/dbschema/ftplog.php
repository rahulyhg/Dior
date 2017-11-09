<?php
$db['ftplog']=array (
  'columns' =>
  array (
    'ftp_log_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'io_type' =>
    array (
      'type' => array(
		 'in'=>'下载',
		 'out'=>'上传',
	  ),
      'required' => true,
      'in_list' => true,
	  'default'=>'out',
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
      'label' => '操作时间',
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
    'file_local_route' =>
    array (
      'type' => 'varchar(225)',
      'label' => '上传的本地文件',
      'width' => 100,
      'in_list' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
	'file_ftp_route' =>
    array (
      'type' => 'varchar(225)',
      'label' => '服务器文件',
      'width' => 280,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'memo' =>
    array (
      'type' => 'varchar(225)',
      'label' => '备注',
      'width' => 150,
      'in_list' => true,
    ),
  ),
  'comment' => 'ftp操作日志表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);