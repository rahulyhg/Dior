<?php
$db['return_address']=array (
  'columns' => 
  array (
   'address_id'=>array(
   'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
   ),
   'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'editable' => false,
      'label' => '店铺',
      'in_list' => true,
      'default_in_list' => true,
    ),

    'shop_type' =>
    array (
      'type' => 'varchar(50)',
      'label' => '店铺类型',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'default_in_list' => true,
    ),
    'contact_id' => 
    array (
      'type' => 'int',
      'editable' => false,
      'label'=>'地址库ID',
      'default_in_list' => true,
    ),
    'contact_name' =>
    array (
      'type' => 'varchar(50)',
      'label'=>'联系人姓名',
       'in_list' => true,
      'default_in_list' => true,
      
    ),
    'province' => 
    array (
      'type' => 'varchar(20)',
      'editable' => false,
      'label' => '省',
       'in_list' => true,
      'default_in_list' => true,
    ),
    'city' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '市',
      'width' => 90,
      'default_in_list' => true,
    ),
    'country' => 
    array (
      'type' => 'varchar(20)',
      'label'=>'区',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'addr' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'label' => '详细街道地址',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 75,
    ),
    'zip_code' =>
    array (
      'type' => 'varchar(10)',
      'editable' => false,
      'label' => '地区邮政编码',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 150,
    ),

    'phone' =>
    array (
      'type'=>'varchar(18)',
      'label'=>'电话号码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'mobile_phone' => 
    array (
      'type'=>'varchar(15)',
      'label'=>'手机号码',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'seller_company' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
      'label' => '公司名称',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '备注',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'area_id'=>array(
        'type'=>'number',
        'label'=>'区域ID',
        'in_list' => true,
      'default_in_list' => true,
    ),
    'get_def' =>
    array (
      'type' => 'bool',
      'label' => '是否默认取货地址',
      'comment' => '是否默认取货地址',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'default' => 'false',
    ),
    'cancel_def' =>
    array(
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,      
      'comment'=>'是否默认退货地址',
      'label'=>'是否默认退货地址',      
    ),
   
    'modify_date'=>array(
        'type'=>'time',
        'label'=>'修改日期时间', 

    ),
    
),
 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment'=>'地址表',
);