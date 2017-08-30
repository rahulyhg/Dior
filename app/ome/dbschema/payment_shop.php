<?php
$db['payment_shop']=array (
  'columns' => 
  array (
    'pay_bn' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '支付编号',
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '绑定店铺',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
    ),
  ),
  'index' => 
  array (
    'ind_pay_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'pay_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);