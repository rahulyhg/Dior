<?php
$db['api_log_copy']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => true,
      'label' => '日志编号',
      'width' => 100,
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
      'width' => 330,
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
    'params' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'msg' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'api_type' => 
    array (
      'type' => 
        array (
          'response' => '响应',
          'request' => '请求',
        ),
      'editable' => false,
      'default' => 'request',
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'label' => '同步类型',
      'width' => 70,
    ),
    'error_lv' =>
    array (
      'type' => 
      array (
        'normal' => '正常',
        'system' => '系统级',
        'application' => '应用级',
        'warning' => '警告',
      ),
      'editable' => false,
      'default' => 'normal',
      'required' => true,
      'label' => '错误级别',
    ),
    'marking_value' =>
    array (
      'type' => 'varchar(80)',
      'edtiable' => false,
    ),
    'marking_type' =>
    array (
      'type' => 'varchar(32)',
      'edtiable' => false,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'edtiable' => false,
    ),
    'msg_id' =>
    array (
      'type' => 'varchar(60)',
      'filtertype' => 'yes',
      'filterdefault' => true,
      'label' => 'msg_id',
      'width' => 60,
      'edtiable' => false,
    ),
    'retry' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'edtiable' => false,
      'in_list' => true,
      'label' => '重试次数',
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'label' => '发起时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'last_modified' =>
    array (
      'label' => '结束时间',
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'index' =>
  array (
    'ind_status' =>
    array (
        'columns' =>
        array (
          0 => 'status',
        ),
    ),
    'ind_api_type' =>
    array (
        'columns' =>
        array (
          0 => 'api_type',
        ),
    ),
    'ind_error_lv' =>
    array (
        'columns' =>
        array (
          0 => 'error_lv',
        ),
    ),
    'ind_marking' =>
    array (
        'columns' =>
        array (
          0 => 'marking_value',
          1 => 'marking_type',
        ),
        'prefix' => 'unique',
    ),
  ),
  'comment' => 'api日志备份副表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
