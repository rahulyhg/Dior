<?php
#订单预处理表，记录预处理的一些状态 wangkezheng 2014-08-12
$db['order_preprocess']=array (
        'columns' =>
        array (
                'preprocess_order_id' =>
                array (
                        'type' => 'table:orders@ome',
                        'required' => true,
                        'pkey' => true,
                        'editable' => false,
                ),
                'preprocess_type' =>
                array (
                        'type' =>
                        array (
                               'pay' => 'pay',
                               'flag' =>'flag',
                               'logi' =>'logi',
                               'member' => 'member',
                               'ordermulti' => 'ordermulti',
                               'ordersingle' => 'ordersingle',
                               'branch' => 'branch',
                               'store' => 'store',
                               'abnormal' => 'abnormal',
                               'oversold' => 'oversold',
                               'tbgift' => 'tbgift',
                               'shopcombine' => 'shopcombine',
                               'crm' => 'crm',
                               'tax' =>'tax'
                        ),
                        'default' => 'crm',
                        'required' => true,
                        'label' => '预处理类型',
                        'editable' => false,
                ),
                'preprocess_status' =>
                array (
                        'type' =>
                        array (
                                0 => '未完成',
                                1=>'已完成',
                        ),
                        'default' => '0',
                        'required' => true,
                        'label' => '预处理状态',
                ),
        ),
        'engine' => 'innodb',
        'version' => '$Rev:  $',
);