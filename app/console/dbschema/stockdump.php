<?php
$db['stockdump']=array (
  'columns' =>
  array (
    'stockdump_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'stockdump_bn' =>
    array (
      'type' => 'varchar(20)',
      'required' => true,
      'label' => '编号',
      'width' => 130,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list'=>true,
    ),
    'create_time' =>
    array (
        'type' => 'time',
        'label' => '生成日期',
        'width' => 140,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'filtertype' => 'time',
        'filterdefault' => true,
    ),
    'operator_name' =>
    array (
        'type' => 'varchar(50)',
        'label' => '操作人员',
        'width' => 110,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'searchtype' => 'has',
        'filtertype' => 'normal',
        'filterdefault' => true,
    ),
    'in_status' =>
    array (
      'type' =>
      array (
        0 => '未入库',
        1 => '已入库',
        2 => '失败',
      ),
      'default' => '0',
      'label' => '单据状态',
    ),
   
   
	'self_status' =>
    array (
      'type' => array(
			0=>'取消',
			1=>'生效',
			2=>'关闭',
	  ),
	  'default' => '1',
	  'label' => '取消状态',
    ),
	'from_branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => false,

    ),
	'to_branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => false,

    ),
	'from_branch_name'=>
    array(
        'type' => 'varchar(30)',
        'label' => '调出仓库',

    ),
	'to_branch_name'=>
    array(
        'type' => 'varchar(30)',
        'label' => '调入仓库',

    ),
    'confirm_type' =>
    array (
      'type' =>
      array (
        0 => '无需确认',
        1 => '未确认',
		      2 => '已确认',
      ),
      'default' => '1',
      'label' => '确认状态',
    ),
	'confirm_name' =>
    array (
      'type' => 'varchar(30)',
      'label' => '确认人',
      'width' => 80,
    ),
    'confirm_time' =>
    array (
        'type' => 'time',
        'label' => '确认日期',
        'width' => 140,
        'editable' => false,
    ), 
    'response_time' =>
    array (
        'type' => 'time',
        'label' => '出入库响应时间',
        'width' => 140,
        'editable' => false,
    ), 
    'memo' =>
    array (
        'type' => 'longtext',
        'editable' => false,
    ),
    'branch_memo' =>
    array (
        'type' => 'longtext',
        'editable' => false,
    ),
     'sync_status' =>
    array (
      'type' => array(
            'nosync' => '未同步',
            'running' => '运行中',
            'fail'=>'失败',
            'success'=>'成功',
        ),
      'default' => 'nosync',
      'label' => '同步状态',
      'editable' => false,
    ),
  ),
  'comment' => '库存出入库单列表',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
