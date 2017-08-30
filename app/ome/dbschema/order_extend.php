<?php
$db['order_extend']=array(
  'columns' => array(
        'order_id' => array(
            'type'     => 'table:orders@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'pkey' => true,
            'comment'  => '订单号',
        ),
        'receivable' =>
        array (
            'type' => 'money',
            'default' => '0',
            'label' => '应收费用',
            'required' => true,
            'editable' => false,
        ),
        'sellermemberid' => array(
            'type' => 'varchar(255)',
            'label' => '卖家会员登录名',
        ),
        'extend_status' =>
          array (
                  'type' => 'varchar(30)',
                  'default' => '0',
                  'comment' => '订单扩展状态(比如收货人信息发生变更)',
                  'editable' => false
          ),          
  ),
  'engine'  => 'innodb',
  'version' => '$Rev: 40912 $',
  'comment' => '订单扩展表',
);