<?php
$db['order_outstorage']=array(
  'columns' => array(
        'order_id' => array(
            'type' => 'table:orders@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'pkey' => true,
            'comment' => '订单号',
        ),
  ),
  'engine'  => 'innodb',
  'version' => '$Rev: 40912 $',
  'comment' => '出库失败表',
);