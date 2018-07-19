<?php 
$db['qmrequest_log']=array (
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
    'original_id' =>
    array (
        'type' => 'int(32)',
        'editable' => false,
        'label' => '单据Id',
        'width' => 200,
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
        'width' => 200,
        'order' => '3',
    ),
    'task_name' =>
    array (
        'type' => 'varchar(50)',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'filtertype' => 'normal',
        'filterdefault' => true,
        'searchtype' => 'has',
        'label' => '接口名称',
        'width' => 200,
    ),
    'status' =>
    array (
        'type' =>
        array (
            'running' => '运行中',
            'success' => '成功',
            'failure' => '失败',
        ),
        'required' => true,
        'default' => 'running',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'editable' => false,
        'filtertype' => 'normal',
        'filterdefault' => true,
        //'searchtype' => 'has',
        'label' => '状态',
        'width' => 60,
    ),
    'original_params' =>
    array (
        'type' => 'longtext',
        'editable' => false,
        'label' => '请求参数',
    ),
    'response' =>
    array (
        'type' => 'longtext',
        'editable' => false,
        'label' => '响应参数',
    ),
    'msg' =>
    array (
        'type' => 'varchar(255)',
        'editable' => false,
        'label' => '接口信息',
        'in_list' => true,
        'default_in_list' => true,
    ),
    'log_type' =>
    array (
        'type' => 'varchar(32)',
        'editable' => false,
        'label' => '日志类型',
        'in_list' => true,
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
    ),
    'last_modified' =>
    array (
        'type' => 'last_modify',
        'label' => '最后重试时间',
        'width' => 130,
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
    ),
    'res_msg' =>
    array (
        'type' => 'varchar(255)',
        'editable' => false,
        'label' => '接口返回信息',
        'in_list' => true,
        'default_in_list' => true,
    ),
    'try_num'=>
    array(
        'type' => 'int(32)',
        'default'=> 0,
        'editable' => false,
        'label' => '重试次数',
        'in_list' => true,
        'default_in_list' => true,
    ),
    'param1'=>
    array(
        'type' =>'varchar(255)',
        'default' => '',
        'editable' => false,
        'label' => '附加属性1'
    ),
    'param2'=>
    array(
        'type' =>'varchar(255)',
        'default' => '',
        'editable' => false,
        'label' => '附加属性2'
    ),
    ),
    'comment' => 'qimen请求日志',
    'engine' => 'innodb',
    'version' => '$Rev: 44513 $',
);
