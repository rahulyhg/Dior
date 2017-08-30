<?php
$db['sms_sample_items'] = array (
    'columns' => array(
        'iid' => array(
            'type' => 'int',
            'required' => true,
            'label' => 'id',
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'id'=>array(
            'type' => 'int',
            'required' => true,
            'label' => '主模板id',
       
            'editable' => false,
        ),

        'sample_no' => array(
            'type' => 'varchar(50)',
            'required' => true,
            'label' => '模板编号',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'title' => array(
            'type' => 'varchar(50)',
            'required' => true,
            'label' => '模板标题',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'content' => array(
            'type' => 'varchar(200)',
            'required' => true,
            'label' => '模板内容',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'status' => array(
            'type' => array(
                '0' => '关闭',
                '1' => '开启',
            ),
            'required' => true,
            'default' => '1',
            'in_list'=>true,
            'label' => '模板状态',
            'default_in_list'=>true,
        ),
     
        'approved' =>
            array (
              'type' =>array(
                '0'=>'等待审核',
                '1'=>'通过',
                '2'=>'拒绝',
              ),
              'default' => '0',
              'required' => true,
              'label' => '审核状态',
              'width' => 75,
              'hidden' => true,
              'editable' => false,
               'in_list'=>true,
            'default_in_list'=>true,
            ),
            'sync_status' =>
            array (
              'type' => 'bool',
              'default' => 'false',
              'required' => true,
              'label' => '同步状态',
              'width' => 75,
              'hidden' => true,
              'editable' => false,
               'in_list'=>true,
            'default_in_list'=>true,
            ),
            'sync_reason' =>
            array (
              'type' => 'varchar(200)',
            
         
              'label' => '原因',
              'width' => 75,
              'hidden' => true,
              'editable' => false,
               'in_list'=>true,
            'default_in_list'=>true,
            ),
        'tplid' =>
            array (
                'type' => 'varchar(25)',
                'label' => '外部模板ID',

            ),
        'disabled' =>
        array (
          'type' => 'bool',
          'required' => true,
          'default' => 'false',
          'editable' => false,
        ),
        'isapproved'=>array(
            'type' => 'bool',
          'required' => true,
          'default' => 'false',
          'editable' => false,
        ),
         'op_name' =>
            array (
      'type' => 'varchar(30)',
      'editable' => false,
        ),
        'createtime' => array(
            'type' => 'time',

            'label' => '创建时间',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'approvedtime' => array(
            'type' => 'time',

            'label' => '审核时间',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
    ),

    'version' => '$Rev: 44514 $',
);
