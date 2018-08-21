<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 13:40
 */
$db['api_log'] = array(
    'columns' =>
        array(
            'id' => array(
                'type' => 'number',
                'required' => true,
                'pkey' => true,
                'extra' => 'auto_increment',
                'label' => '请求ID',
            ),
            'api_handler' => array(
                'type' => array(
                    'request' => '请求',
                    'response' => '应答'
                ),
                'default' => 'request',
                'required' => true,
                'label' => 'API动作',
                'in_list' => true,
                'default_in_list' => true,
                'filtertype' => 'yes',
                'filterdefault' => true,
            ),
            'api_name' => array(
                'type' => 'varchar(50)',
                'required' => true,
                'label' => 'API名称',
                'in_list' => true,
                'default_in_list' => true,
                'searchtype' => 'has',
                'filtertype' => 'yes',
                'filterdefault' => true,
            ),
            'api_request_time' => array(
                'type' => 'time',
                'required' => true,
                'label' => '请求时间',
                'in_list' => true,
                'default_in_list' => true,
                'filtertype' => 'yes',
                'filterdefault' => true,
            ),
            'api_check_time' => array(

                'type' => 'varchar(30)',
                'required' => true,
                'label' => '校验时间戳',
                'filtertype' => 'yes',
                'filterdefault' => true,
            ),
            'api_status' => array(
                'type' => array('-' => '-', 'fail' => '失败', 'success' => '成功'),
                'required' => true,
                'label' => 'API返回状态',
                'in_list' => true,
                'default_in_list' => true,
                'filtertype' => 'yes',
                'filterdefault' => true,
            ),
            'http_runtime' => array(
                'type' => 'decimal(10,6)',
                'label' => '执行时间',
                'in_list' => true,
                'default_in_list' => true,
            ),
            'http_method' => array(
                'type' => array('GET' => 'GET', 'POST' => 'POST','WebService'=>'WebService'),
                'required' => true,
                'label' => '请求类型',
                'in_list' => true,
                'default_in_list' => true,
                'filtertype' => 'yes',
                'filterdefault' => true,
            ),
            'http_url' => array(
                'type' => 'text',
                'required' => true,
                'label' => '请求地址',
            ),
            'http_request_data' => array(
                'type' => 'serialize',
                'label' => '请求数据',
            ),
            'http_response_status' => array(
                'type' => 'int',
                'required' => true,
                'label' => '返回状态',
                'in_list' => true,
            ),
            'http_response_data' => array(
                'type' => 'serialize',
                'required' => true,
                'label' => '返回结果',
            ),
            'sys_error_data' => array(
                'type' => 'serialize',
                'required' => true,
                'label' => 'API ERROR',
            )
        ),
    'index' =>
        array(
            'idx_request_time' => array(
                'columns' => array(
                    'api_request_time',
                )
            ),
            'idx_api_handler_api_status' => array(
                'columns' => array(
                    'api_handler',
                    'api_status',
                )
            )
        ),
    'comment' => 'creditorderapi请求日志',
);
