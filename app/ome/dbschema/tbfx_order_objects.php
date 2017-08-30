<?php
$db['tbfx_order_objects']=array(
  'columns' => array(
          'order_id' => array(
            'type'     => 'table:orders@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'comment'  => '分销订单号',
          ),
          'obj_id' => array(
            'type' => 'table:order_objects@ome',
            'required' => true,
            'default'  => 0,
            'editable' => false,
            'comment'  => '订单obj主键ID',
          ),
          'fx_oid' => array(
            'type'     => 'bigint(20)',
            'default'  => 0,
            'editable' => false,
            'comment'  => '淘宝分销子采购单id',
          ),
        'tc_order_id' => array(
            'type'     => 'bigint(20)',
            'default'  => 0,
            'editable' => false,
            'comment'  => '淘宝分销的子订单ID (经销不显示)',
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