<?php
/**
 * rabbitmq 访问对像
 *
 * @author hzjsq@foxmail.com
 * @version 0.1 b
 */

//判断使用的 rabbitmq 访问插件

if (defined('__RABBITMQ_INTERFACE__') && strtolower(__RABBITMQ_INTERFACE__ == 'pecl') && class_exists('AMQPConnection')) {

    //使用php的amqp插件访问rabbitmq
    class taskmgr_connecter_rabbitmq extends taskmgr_connecter_rabbitmq_pecl {

    }
} else {

    //使用php原生代码写在写phpamqplib访问rabbitmq
    class taskmgr_connecter_rabbitmq extends taskmgr_connecter_rabbitmq_stocket {

    }
}