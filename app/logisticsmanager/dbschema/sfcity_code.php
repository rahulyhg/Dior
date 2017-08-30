<?php
$db['sfcity_code']=array (
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
    'province' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '省',
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 10,
    ),
    'city' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'label' => '市',
      'width' => 130,
      'searchtype' => 'has',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 20,
    ),
    'city_crc32' =>
    array (
      'type' => 'bigint(13)',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => '市级crc32值',
      'width' => 100,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 30,
    ),
    'city_code' =>
    array (
      'type' => 'varchar(10)',
      'required' => true,
      'default' => '',
      'editable' => false,
      'label' => '城市代码',
      'width' => 100,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 40,
    ),
  ),
  'index' =>
  array (
    'ind_city_crc32' => array (
      'columns' => array (
        0 => 'city_crc32',
      ),
    ),
  ),
  'comment' => '顺丰城市代码表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);