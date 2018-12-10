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
        'original_bn' =>
        array (
            'type' => 'varchar(50)',
            'editable' => false,
            'in_list' => true,
            'default' =>'',
            'default_in_list' => true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'searchtype' => 'has',
            'label' => '单据号',
            'width' => 200,
            'order' => '3',
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
        'queue_title' => array (
            'type'     => 'varchar(50)',
            'label'    => app::get('base')->_('队列名称'),
            'required' => true,
            'is_title' => true,
            'in_list'  => true,
            'width'    => 200,
            'default_in_list' => true,
            'filtertype'      => 'yes',
            'filterdefault'   => true,
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
        'worker' => array(
            'type'     => 'varchar(200)',
            'required' => true,  
            'width'    => 200,
            'label'    => app::get('base')->_('执行方法'),
            'in_list'  => true,
            'default_in_list' => true,
        ),
        'api_params' =>
        array (
            'type' => 'serialize',
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
        'queue_type' =>
            array(
            'label' => app::get('base')->_('队列类型'),
            'type'  => array(
                'return'      => app::get('base')->_('退货单入库创建'),
                'delivery'    => app::get('base')->_('发货单创建'),
                'do_delivery' => app::get('base')->_('发货单确认'),
                'do_return'   => app::get('base')->_('退货入库单确认'),
            ),
            'required' => true,
            'in_list'  => true,
            'width'    => 100,
            'filterdefault'   => true,
            'filtertype'      => 'yes',
            //'searchtype'      => 'has',
            'default_in_list' => true,
        ),
        'repeat_num' => array(
            'type'              => 'int(3)',
            'label'             => '重发次数',
            'in_list'           => true,
            'default_in_list'   => true,
            //'searchtype'        => 'has',
            'filtertype'        => 'yes',
            'filterdefault'     => true,
            'default'           => 0,
            'width'             => 60,
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