<?php
$db['payment_cfg']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'custom_name' => 
    array (
      'type' => 'varchar(100)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
      'label' => '支付方式名',
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'pay_bn' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '支付编号',
    ),
    'des' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'pay_type' =>
    array (
      'type' => 'varchar(100)',
      'default' => 'online',
      'width' => 75,
      'editable' => false,
      'label' => '支付类型',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
  ),
  'index' => 
  array (
    'uni_pay_bn' => 
    array (
      'columns' => 
      array (
        0 => 'pay_bn',
      ),
      'prefix' => 'UNIQUE',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);