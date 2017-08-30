<?php
$db['brand']=array (
  'columns' =>
  array (
    'brand_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => '品牌id',
      'width' => 150,
      'comment' => '品牌id',
      'editable' => false,
    ),
    'brand_name' =>
    array (
      'type' => 'varchar(50)',
      'label' => '品牌名称',
      'width' => 160,
      'is_title' => true,
      'required' => true,
      'comment' => '品牌名称',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
      'in_list' => true,
      'default_in_list' => true,
     ),
     'brand_keywords' =>
    array (
      'type' => 'longtext',
      'label' => '品牌别名',
      'width' => 150,
      'comment' => '品牌别名',
      'editable' => false,
      'searchtype' => 'has',
      'in_list' => true,
       'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
    ),
    'brand_url' =>
    array (
      'type' => 'varchar(255)',
      'label' => '品牌网址',
      'width' => 350,
      'comment' => '品牌网址',
      'editable' => false,
      'searchtype' => 'has',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'brand_desc' =>
    array (
      'type' => 'longtext',
      'comment' => '品牌介绍',
      'editable' => false,
      'label' => '品牌介绍',
    ),
    'brand_logo' =>
    array (
      'type' => 'varchar(255)',
      'comment' => '品牌图片标识',
      'editable' => false,
      'label' => '品牌图片标识',
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'comment' => '失效',
      'editable' => false,
      'label' => '失效',
    ),
    'ordernum' =>
    array (
      'type' => 'number',
      'label' => '排序',
      'width' => 150,
      'comment' => '排序',
      'editable' => false,
    ),
  ),
  'comment' => '品牌表',
  'index' =>
  array (
    'ind_disabled' =>
    array (
      'columns' =>
      array (
        0 => 'disabled',
      ),
    ),
    'ind_ordernum' =>
    array (
      'columns' =>
      array (
        0 => 'ordernum',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);