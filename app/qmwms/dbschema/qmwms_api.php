<?php
 
$db['qmwms_api'] = array(
    'columns' =>
    array (
    'api_id' =>
    array (
        'type' => 'int(32)',
        'required' => true,
        'pkey' => true,
        'editable' => false,
        'extra' => 'auto_increment',
        'width' => 100,
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
    'api_params' =>
    array (
        'type' => 'longtext',
        'editable' => false,
        'label' => '配置参数',
    ),
    ),
    'comment' => 'ERP奇门对接配置',
    'engine' => 'innodb',
    'version' => '$Rev: 44513 $',
);