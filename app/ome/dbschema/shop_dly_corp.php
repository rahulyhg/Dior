<?php
$db['shop_dly_corp']=array (
  'columns' => 
  array (
    'shop_id' => 
    array (
      'type' => 'table:shop@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'corp_id' =>
    array (
      'type' => 'table:dly_corp@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'crop_name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'label' => '物流公司名称',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'is_title' => true,
    ),
  ),
  'index' =>
  array (
    'ind_corp_shop' =>
    array (
        'columns' =>
        array (
          0 => 'corp_id',
          1 => 'shop_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);