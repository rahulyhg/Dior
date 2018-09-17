<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

$db['kafka_queue'] = array (

    'columns' => array (
        'queue_id' => array (
                'type'     => 'number',
                'pkey'     => true,
                'extra'    => 'auto_increment',
                'label'    => 'ID',
                'required' => true,
                'editable' => false,
            ),
        'queue_title' => array (
                'type'     => 'varchar(50)',
                'label'    => app::get('base')->_('队列名称'),
                'required' => true,
                'is_title' => true,
                'in_list'  => true,
                'width'    => 200,
                'default_in_list' => true,
            ),
        'status' => array(
            'label' => app::get('base')->_('状态'),
            'type'  => array(
                'running'   => app::get('base')->_('运行中'),
                'hibernate' => app::get('base')->_('休眠中'),
                'paused'    => app::get('base')->_('已暂停'),
                'failure'   => app::get('base')->_('执行失败'),
            ),
            'required' => true,
            'default'  => 'hibernate',
            'in_list'  => true,
            'width'    => 100,
            'default_in_list' => true,
        ),
        'worker' => array(
            'type'     => 'varchar(200)',
            'required' => true,
            'width'    => 200,
            'label'    => app::get('base')->_('执行脚本方法'),
        ),
        'start_time' => array(
            'type'     => 'time',
            'label'    => app::get('base')->_('任务产生时间'),
            'required' => true,
            'in_list'  => true,
            'width'    => 150,
        ),
        'params' => array(
            'type'     => 'serialize',
            'label'    => app::get('base')->_('参数'),
            'required' => true,
            'comment'  => app::get('base')->_('参数，通常就是filter'),
        ),
        'errmsg' => array(
            'type'    => 'varchar(255)',
            'width'   => 200,
            'in_list' => true,
            'label'   => app::get('base')->_('错误信息'),
            'default_in_list' => true,
        ),
    ),
    'index' => array (
        'ind_worker' => array (
            'columns' => array (
                0 => 'worker',
            ),
        ),
        'ind_status' => array (
            'columns' => array (
                0 => 'status',
            ),
        ),
    ),
    'engine'       => 'innodb',
    'version'      => '$Rev: 40912 $',
    'ignore_cache' => true,
    'comment'      => 'kafka队列表',
);

// 队列需要id从大到小的执行
