<?php
$db['area']=array (
    'columns' =>
    array (
        'area_id' =>
        array (
            'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),

        'local_name' =>
        array (
            'type' => 'varchar(50)',
            'required' => true,
            'default' => '',

            'label'=>'名称',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
            'editable' => false,
        ),

        'region_id' =>
        array (
            'type' => 'varchar(255)',
            'editable' => false,
        ),

         'region_name' =>
        array (
            'type' => 'longtext',
            'label'=>'包含区域名称',
            'width'=>100,
            'default_in_list'=>true,
            'in_list'=>true,
             'width'=>400,
        ),
        'ordernum' =>
        array (
            'type' => 'number',
            'editable' => true,
        ),
        'disabled' =>
        array (
            'type' => 'bool',
            'default' => 'false',
            'editable' => false,
        ),
    ),

);
