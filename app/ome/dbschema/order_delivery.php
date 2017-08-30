<?php
$db['order_delivery']=array (
      'columns' =>
        array(
            'id' => array(
                'type' => 'int(10)',
                'pkey' => true,
                'extra' => 'auto_increment',
                'required' => true,
                'label' => '编号',
                'filterdefault' => true,
                'in_list' => true,
                'default_in_list' => false,
                'width' => 60,
                'hidden' => true,
                'order'=>10,
            ),
            'order_bn' =>
            array (
              'type' => 'varchar(32)',
              'required' => true,
              'label' => '订单号',
            ),
            'bn' =>
            array (
              'type' => 'text',
              'label' => '商品编号',
            ),
            'oid' => 
            array (
              'type' => 'text',
              'label' => '子订单号',
            ),
            'quantity' =>
            array (
              'type' => 'varchar(255)',
              'label' => '购买数量',
            ),
            'promotion_id' =>
            array (
              'type' => 'varchar(255)',
              'label' => '子订单号',
            ),
            'dateline' => 
            array (
              'type' => 'time',
              'required' => true,
              'default' => '0',
              'label' => '生成日期',
            ),
    ),
    'index' =>
      array (
        'ind_order_bn' =>
        array (
            'columns' =>
            array (
              0 => 'order_bn',
            ),
        ),
        
    ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);