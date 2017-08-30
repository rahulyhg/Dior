<?php
$db['sms_bind'] = array (
	'columns' => array(
        'bind_id' => array(
            'type' => 'int unsigned',
            'required' => true,
            'label' => '绑定编号',
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'tid' => array(
            'type' => 'int(11)',
            'required' => true, 
            'label' => "规则名称",
            'default'  =>'0',
            'in_list'=>false,
            'default_in_list'=>false,
            'editable' => false,
        ),
        'id' => array(  
            'type' => 'table:sms_sample@taoexlib',
            'label' => '模板编号',
            'required' => true,
            'default'  =>'0',
            'in_list'=>true,
            'default_in_list'=>true,
        ),
        'is_default' => array(
            'type' => array(
                '0' => '否',
                '1' => '是',
            ),
            'required' => true,
            'default' => '0',
            'in_list'=>true,
            'label' => '是否默认',
            'default_in_list'=>true,
        ),
        'is_send' => array(
            'type' => array(
                '0' => '不发送',
                '1' => '发送',
            ),
            'required' => true,
            'default' => '1',
            'in_list'=>true,
            'label' => '是否发送',
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
            'label' => '绑定状态',
            'default_in_list'=>true,
        ),
    ),
    'version' => '$Rev: 44513 $',
);
