<?php
$db['queue'] = array(
    'columns' =>
    array (
        'id' =>
        array (
            'type' => 'int(32)',
            'required' => true,
            'pkey' => true,
            'editable' => false,
            'extra' => 'auto_increment',
            'width' => 100,
        ),
        'status' =>
        array (
          'type' =>
          array (
            '0' => '睡眠',
            '1' => '运行中',
            '2' => '失败',
          ),
          'default' => '0',
          'required' => true,
          'label' => '队列状态',
          'filtertype' => 'yes',
          'filterdefault' => true,
          'in_list' => true,
          'default_in_list' => true,
        ),
        'api_method' =>
        array (
          'type' =>'varchar(40)',
          'default' => 'deliveryOrderConfirm',
          'required' => true,
          'label' => '方法',
          'filtertype' => 'yes',
          'filterdefault' => true,
          'in_list' => true,
          'default_in_list' => true,
        ),
        'api_params' =>
        array (
            'type' => 'longtext',
            'editable' => false,
            'label' => '请求参数',
        ),
        'createtime' =>
        array (
            'type' => 'time',
            'label' => '创建时间',
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'last_modified' =>
        array (
            'label' => '最后更新时间',
            'type' => 'last_modify',
            'width' => 130,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
        'msg' =>
        array (
          'type' =>'varchar(140)',
          'default' => '',
          'required' => false,
          'label' => '错误信息',
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
        'ind_createtime' =>
        array (
            'columns' =>
            array (
              0 => 'createtime',
            ),
        ),
      ),
    'comment' => 'ERP奇门队列',
    'engine' => 'innodb',
    'version' => '$Rev: 44513 $',
);