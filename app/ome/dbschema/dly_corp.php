<?php
$db['dly_corp']=array (
  'columns' =>
  array (
    'corp_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'branch_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'all_branch' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'true',
      'editable' => false,
    ),
    'type' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
    ),
    'name' =>
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
    'disabled' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'website' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'request_url' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
    ),
    'daily_process' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 100,
    ),
    'firstunit' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'required' => true,
    ),
    'continueunit' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'required' => true,
    ),
    'protect' =>
    array (
      'type' => 'bool',
      'editable' => false,
      'required' => true,
      'default' => 'false',
    ),
    'protect_rate' =>
    array (
      'type' => 'decimal(6,3)',
      'editable' => false,
    ),
    'minprice' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'setting' =>
    array (
      'type' =>
      array (
        0 => '指定地区费用',
        1 => '统一设置',
      ),
      'editable' => false,
      'required' => true,
      'default' => '1',
      'label' => '地区费用类型',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'firstprice' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'continueprice' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'dt_expressions' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'dt_useexp' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'area_fee_conf' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'is_cod' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'editable' => false,
      'label' => '适用店铺',
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'channel_id' =>
    array (
      'type' => 'table:channel@logisticsmanager',
      'editable' => false,
      'comment' => '来源主键',
      'label' => '面单来源',
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'tmpl_type' =>
    array (
      'type' => array(
        'normal' => '普通面单',
        'electron' => '电子面单',
      ),
      'editable' => false,
      'required' => true,
      'default' => 'normal',
      'label' => '快递模板类型',
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'prt_tmpl_id' =>
    array (
      'type' => 'table:print_tmpl@wms',
      'default' => '0',
      'editable' => false,
    ),
    'weight' =>
    array (
      'type' => 'number',
      'edtiable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '权重',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);