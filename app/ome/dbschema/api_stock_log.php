<?php
$db['api_stock_log']=array (
  'columns' =>
  array (
    'log_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
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
      'in_list' => false,
      'default_in_list' => false,
      'filtertype' => false,
      'filterdefault' => false,
      'searchtype' => false,
      'label' => '任务名称',
      'width' => 380,
	  'order' => 10,
    ),
    'status' =>
    array (
      'type' =>
        array (
          'running' => '同步中',
          'success' => '成功',
          'fail' => '失败',
          'sending' => '等待同步',
        ),
      'required' => true,
      'default' => 'sending',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
      'filtertype' => false,
      'filterdefault' => false,
      'label' => '同步状态',
      'width' => 100,
	  'order' => 50,
    ),
    'worker' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'required' => true,
      'label' => 'api方法名',
      'in_list' => false,
    ),
    /*'params' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '任务参数',
      'filtertype' => false,
	  'filterdefault' => false,
    ),*/
    'msg' =>
    array (
      'type' => 'varchar(1000)',
      'editable' => false,
      'label' => '响应消息',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 150,
	  'order' => 55,
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
      'in_list' => false,
      'default_in_list' => false,
      'filtertype' => false,
      'filterdefault' => false,
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
      'filtertype' => false,
      'filterdefault' => false,
    ),
    /*'memo' =>
    array (
      'type' => 'text',
      'edtiable' => false,
    ),*/
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
      'width' => 60,
      'edtiable' => false,
      'in_list' => false,
      'label' => '同步次数',
      'default_in_list' => false,
	  'order' => 60,
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'label' => '发起时间',
      'width' => 150,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
	  'order' => 70,
    ),
    'last_modified' =>
    array (
      'label' => '响应时间',
      'type' => 'last_modify',
      'width' => 150,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
	  'order' => 80,
    ),
	'shop_id' =>
    array (
      'label' => '店铺id',
      'type' => 'varchar(32)',
      'width' => 150,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
	  'order' => 30,
    ),
	'product_id' =>
    array (
      'label' => '货品id',
      'type' => 'number',
      'width' => 80,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
	  'order' => 40,
    ),
    'crc32_code' =>
    array (
      'type' => 'bigint(20)',
      'required' => true,
      'label' => '店铺货品crc32值',
    ),
	'product_bn' =>
    array (
      'label' => '货号',
      'type' => 'varchar(32)',
      'width' => 120,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
	  'filterdefault' => true,
	  'order' => 12,
    ),
    'product_name' =>
    array (
      'label' => '商品名称',
      'type' => 'varchar(100)',
      'width' => 240,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
	  'filterdefault' => true,
	  'order' => 11,
    ),
    'shop_name' =>
    array (
      'label' => '店铺名称',
      'type' => 'varchar(100)',
      'width' => 200,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
	  'filterdefault' => true,
	  'order' => 20,
    ),
	'store' =>
    array (
      'label' => '库存数',
      'type' => 'number',
      'width' => 80,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'nequal',
      'filtertype' => 'yes',
	  'filterdefault' => true,
	  'order' => 15,
    ),
    'op_user' =>
    array (
      'label' => '操作人',
      'type' => 'varchar(32)',
    ),
    'op_userip' =>
    array (
      'label' => '操作人IP',
      'type' => 'varchar(64)',
    ),
    'op_time' =>
    array (
      'label' => '批量同步时间',
      'type' => 'time',
    ),
  ),
  'index' =>
  array (
    'idx_crc32_code' =>
    array (
        'columns' =>
        array (
          0 => 'crc32_code',
        ),
        'prefix' => 'unique',
    ),
    'idx_status' =>
    array (
        'columns' =>
        array (
          0 => 'status',
        ),
    ),
    'idx_createtime' =>
    array (
        'columns' =>
        array (
          0 => 'createtime',
        ),
    ),
  ),
  'comment' => '库存同步日志',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
