<?php

/**
 * 定义打印批次主表结构
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */
$db['print_queue'] = array(
    'columns' =>
    array(
        'queue_id' =>
        array(
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'md5' =>
        array(
            'type' => 'varchar(32)',
            'required' => true,
            'editable' => false,
            'default_in_list' => false,
            'in_list' => false,
        ),
        'crc' =>
        array(
            'type' => 'bigint',
            'required' => true,
            'required' => true,
            'editable' => false,
        ),
        'opt_id' =>
        array(
            'type' => 'number',
            'required' => true,
            'editable' => false,
            'in_list' => false,
        ),
        'opt_name' =>
        array(
            'type' => 'varchar(64)',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '打印者',
            'comment' => '执行打印的用户s',
        ),
        'ident' =>
        array(
            'type' => 'varchar(64)',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '批次号',
            'comment' => '本次打印的批次号',
        ),
        'create_time' =>
        array(
            'type' => 'time',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '创建时间',
            'comment' => '第一次打引时间',
        ),
        'dly_num' =>
        array(
            'type' => 'number',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '发货单数',
            'comment' => '任务中发货单数量',
        ),
        'dly_bns' =>
        array(
            'type' => 'text',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '发货单编号',
            'comment' => '任务中发货单的编号集合',
        ),
        'dly_orders' =>
        array(
            'type' => 'text',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '订单编号',
            'comment' => '任务中订单号集合',
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);