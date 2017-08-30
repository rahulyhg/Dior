<?php
$db['request_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'int(32)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'label' => '日志编号',
	  'extra' => 'auto_increment',
      'width' => 100,
    ),
    'original_bn' =>
    array ( 
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
      'label' => '单据号',
      'width' => '150',
      'order' => '3',
    ),
    'task_name' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
      'label' => '任务名称',
      'width' => 350,
    ),
    'status' =>
    array ( 
      'type' => 
        array (
          'running' => '运行中',
          'success' => '成功',
          'fail' => '失败',
          'sending' => '发起中',
        ),
      'required' => true,
      'default' => 'sending',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'label' => '状态',
      'width' => 60,
    ),
    'worker' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'required' => true,
      'label' => 'api方法名',
      'in_list' => false,
    ),
    'original_params' => 
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '原始参数',
    ),
    'sync' => 
    array (
      'type' => array(
        'true' => '同步',
        'false' => '异步'
      ),
      'editable' => false,
      'label' => '同异步类型',
    ),
    'msg' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'log_type' => 
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => '日志类型',
    ),
    'retry' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'width' => 60,
      'edtiable' => false,
      'in_list' => true,
      'label' => '重试次数',
      'default_in_list' => true,
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'label' => '请求发起时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      //'filtertype' => 'time',
      //'filterdefault' => true,
    ),
    'last_modified' =>
    array (
      'label' => '最后重试时间',
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ), 
  'comment' => 'magento请求日志',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
