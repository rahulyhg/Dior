<?php
$db['channel_extend']=array (
  'columns' => 
  array (
  'id' =>
  array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'channel_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,

      'comment' => '渠道主键',
      'label' => '渠道ID',

    ),
  'province' =>
    array (
      'type' => 'varchar(50)',
      'label' => '省',
   
    ), 
    'city' =>
    array (
      'type' => 'varchar(50)',
      'label' => '市',
   
    ), 
     'area' =>
    array (
      'type' => 'varchar(50)',
      'label' => '地区',
   
    ), 
     'address_detail' =>
    array (
      'type' => 'varchar(50)',
      'label' => '具体地址',
   
    ), 
      'waybill_address_id' =>
    array (
      'label' => '地址ID',
      'type' => 'varchar(20)',
      'editable' => false,

    ),
    'cancel_quantity' =>
    array (
      'label' => '取消数量',
      'type' => 'int unsigned',
      'editable' => false,

    ),
    'allocated_quantity' =>
    array (
      'label' => '可用数量',
      'type' => 'int unsigned',
      'editable' => false,

    ),
    'print_quantity' =>
    array (
      'label' => '打印数量',
      'type' => 'int unsigned',
      'editable' => false,

    ),
    'seller_id' =>
    array (
      'label' => '用户ID',
      'type' => 'varchar(32)',
      'editable' => false,

    ),
     'default_sender' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
      'mobile' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'tel' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'shop_name' =>
    array (
      'type' => 'varchar(255)',
      ),
      'zip' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
    ),

  ),
  'comment' => '面单来源表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);