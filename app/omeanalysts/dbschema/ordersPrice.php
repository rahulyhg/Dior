<?php
$db['ordersPrice']=array (
    'columns' => array (
        'id' =>
        array (
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'dates' =>
        array (
          'type' => 'int',
          'label' => '日期',
          'editable' => false,
          'filtertype' => 'time',
          'filterdefault' => true,
          'in_list' => true,
        ),
        'shop_id' =>
        array (
          'type' => 'table:shop@ome',
          'label' => '来源店铺',
          'editable' => false,
          'in_list' => true,
          'filtertype' => 'normal',
          'filterdefault' => true,
        ),
        'interval_id' =>
        array (
            'type' => 'number',
            'label' => '区间ID',
            'in_list' => true,
        ),
        'num' =>
        array (
            'type' => 'int',
            'label' => '数量',
            'in_list' => true,
        ),
      ),
     'index' =>
  array (
    'ind_order_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'dates',
          1 => 'interval_id',
        ),
    ),
  ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);