<?php
$db['shop']=array (
  'columns' =>
  array (
    'shop_id' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'shop_bn' =>
    array (
      'type' => 'varchar(20)',
      'required' => true,
    ),
    'name' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'label' => '店铺名称',
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
      'width' => '120',
    ),
    'shop_type' =>
    array (
      'type' => 'varchar(32)',
      'required' => false,
      'label' => '店铺类型',
      'in_list' => true,
      'default_in_list' => true,
      'width' => '70'
    ),
    'config' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'crop_config' =>
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'last_download_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '上次下载订单时间(终端)',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'last_upload_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '上次上传订单时间(ome)',
      'in_list' => false,
      'default_in_list' => true,
    ),
    'active' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'in_list' => false,
      'default_in_list' => true,
      'editable' => false,
      'label' => '激活',
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'last_store_sync_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '上次库存同步时间',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'area' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'zip' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
    ),
    'addr' =>
    array (
      'type' => 'varchar(255)',
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
    'filter_bn' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'bn_regular' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'express_remark' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'delivery_template' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'order_bland_template' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'node_id' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
    ),
    'node_type' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
    ),
    'api_version' =>
    array (
      'type' => 'char(6)',
      'editable' => false,
    ),
    'addon' =>
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'sw_code' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '售达方编码',
      'required' => false,
    ),    'alipay_authorize' =>
    array (
      'type' => array(
         'true' => '已授权',
         'false' => '未授权'
      ),
      'default' => 'false',
      'editable' => false,
    ),
    'business_type' =>
    array(
      'type' => array(
         'zx' => '直销',
         'fx' => '分销'
      ),
      'label' => '订单类型',
      'default' => 'zx',
      'in_list' => true,
      'default_in_list' => true,
      'editable' => false,
    ),
    'tbbusiness_type' =>
    array(
      'type' => 'char(6)',
      'label' => '淘宝店铺类型',
      'default' => 'other',    
      'in_list' => true,
      'default_in_list' => true,      
      'editable' => false,
    ),
  ),  'index' =>
  array (
    'ind_shop_bn' =>
    array (
        'columns' =>
        array (
          0 => 'shop_bn',
        ),
        'prefix' => 'unique',
    ),
    'ind_node_id' =>
    array (
        'columns' =>
        array (
          0 => 'node_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);