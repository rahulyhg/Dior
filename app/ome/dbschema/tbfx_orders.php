<?php
$db['tbfx_orders']=array(
  'columns' => array(
        'order_id' => array(
            'type'     => 'table:orders@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'comment'  => '分销订单号',
        ),
        'fenxiao_order_id' => array(
            'type' => 'bigint(20)',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'comment'  => '淘宝分销供应商交易ID (用于发货 作为订单号回写到前端)',
        ),
        'tc_order_id' => array(
            'type'     => 'bigint(20)',
            'default'  => 0,
            'editable' => false,
            'comment'  => '淘宝分销的主订单ID (经销不显示)',
        ),
  ),
  'index' => 
  array(
    'uni_fx_orderid' =>
    array(
      'columns' =>
      array (
        0 => 'order_id',
      ),
      'prefix' => 'UNIQUE',
    ),
  ),
  'engine'  => 'innodb',
  'version' => '$Rev: 40912 $',
  'comment' => '淘宝分销主表',
);