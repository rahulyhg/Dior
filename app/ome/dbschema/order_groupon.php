<?php

/**
 * 团购订单结构
 *
 * @author shiyao744@sohu.com
 * @version 0.1b
 */
$db['order_groupon'] = array(
    'columns' =>
    array(
        'order_groupon_id' =>
        array(
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'name' =>
        array(
            'type' => 'varchar(255)',
            'required' => true,
            'editable' => false,
            'default_in_list' => true,
            'in_list' => true,
         	'label' => '标题',
        ),
        'shop_id' =>
	    array (
	      'type' => 'table:shop@ome',
	      'label' => '来源店铺',
	      'width' => 75,
	      'editable' => false,
	      'in_list' => true,
	      'default_in_list' => true,
	      'filtertype' => 'normal',
	      'filterdefault' => true,
	    ),
	     'create_time' =>
        array(
            'type' => 'time',
            'required' => true,
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '创建时间',
            'comment' => '创建时间',
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
            'label' => '操作人',
            'comment' => '操作人',
        ),
 
     
    ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);