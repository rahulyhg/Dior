<?php
$db['tbfx_order_items']=array(
  'columns' => array(   
    'order_id' => array(
        'type'     => 'table:orders@ome',
        'required' => true,
        'default'  => 0,
        'editable' => false,
        'comment'  => '分销订单号',
    ),
    'obj_id' => array(
        'type'     => 'table:order_objects@ome',
        'required' => true,
        'default'  => 0,
        'editable' => false,
        'comment'  => '对应订单obj上的ID',
    ),
    'item_id' => array(
        'type'     => 'table:order_items@ome',
        'required' => true,
        'default'  => 0,
        'editable' => false,
        'comment'  => '对应订单item上的ID',
    ),
    'buyer_payment' => array(
        'type'     => 'money',
        'default'  => 0,
        'editable' => false,
        'comment'  => '代销价(买家支付给分销商的总金额)',
    ), 
    'cost_tax' => array(
        'type'     => 'money',
        'default'  => 0,
        'editable' => false,
        'comment'  => '发票应开金额',
    ),            
  ), 
  'engine'  => 'innodb',
  'version' => '$Rev: 40912 $',
  'comment' => '淘宝分销主表',
);