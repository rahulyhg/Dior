<?php
$db['order_selling_agent']=array (
  'columns' => 
  array (
    'selling_agent_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => '代销人ID',
      'editable' => false,
    ),
    'order_id' => 
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'uname' => 
    array (
      'type' => 'varchar(50)',
      'label' => '用户名',
      'sdfpath' => 'member_info/uname',
      'is_title' => true,
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => 'true',
    ),
    'level' => 
    array (
      'type' => 'varchar(20)',
      'label' => '等级',
      'width' => 75,
      'sdfpath' => 'member_info/level',
      'editable' => false,
    ),
    'name' => 
    array (
      'type' => 'varchar(50)',
      'label' => '姓名',
      'width' => 75,
      'sdfpath' => 'member_info/name',
      'editable' => false,
    ),
    'birthday' => 
    array (
      'label' => '出生日期',
      'type' => 'varchar(20)',
      'sdfpath' => 'member_info/birthday',
      'width' => 30,
      'editable' => false,
    ),
    'sex' => 
    array (
      'type' => 
      array (
        'female' => '女',
        'male' => '男',
      ),
      'sdfpath' => 'member_info/sex',
      'default' => 'female',
      'label' => '性别',
      'width' => 30,
      'editable' => false,
    ),
    'email' => 
    array (
      'type' => 'varchar(60)',
      'label' => 'E-mail',
      'width' => 110,
      'sdfpath' => 'member_info/email',
      'editable' => false,
    ),
    'area' => 
    array (
      'label' => '地区',
      'width' => 110,
      'type' => 'region',
      'sdfpath' => 'member_info/area',
      'editable' => false,
    ),
    'addr' => 
    array (
      'type' => 'varchar(255)',
      'label' => '地址',
      'sdfpath' => 'member_info/addr',
      'width' => 110,
      'editable' => false,
    ),
    'zip' => 
    array (
      'type' => 'varchar(10)',
      'label' => '邮编',
      'width' => 110,
      'sdfpath' => 'member_info/zip',
      'editable' => true,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'mobile' => 
    array (
      'type' => 'varchar(20)',
      'label' => '手机',
      'width' => 75,
      'sdfpath' => 'member_info/mobile',
      'editable' => false,
    ),
    'tel' => 
    array (
      'type' => 'varchar(30)',
      'label' => '固定电话',
      'width' => 110,
      'sdfpath' => 'member_info/tel',
      'editable' => false,
    ),
    'qq' => 
    array (
      'type' => 'varchar(30)',
      'label' => 'qq',
      'width' => 110,
      'sdfpath' => 'member_info/qq',
      'editable' => false,
    ),
    'website_name' => 
    array (
      'type' => 'varchar(50)',
      'label' => '网站名称',
      'width' => 110,
      'sdfpath' => 'website/name',
      'editable' => false,
    ),
    'website_domain' => 
    array (
      'type' => 'varchar(255)',
      'label' => '网站域名',
      'sdfpath' => 'website/domain',
      'editable' => false,
    ),
    'website_logo' => 
    array (
      'type' => 'text',
      'label' => '网站LOGO',
      'sdfpath' => 'website/logo',
      'editable' => false,
    ),
    'addon' => 
    array (
      'type' => 'serialize',
      'label' => '附加字段',
      'editable' => false,
    ),
          
     'seller_name' =>
      array (
          'type' => 'varchar(50)',
          'label' => '卖家姓名',
          'sdfpath' => 'seller/seller_name',
          'editable' => false,
      ), 
    'seller_mobile' =>
      array (
          'type' => 'varchar(30)',
          'label' => '卖家电话号码',
          'sdfpath' => 'seller/seller_mobile',
          'editable' => false,
      ),          
      'seller_phone' =>
      array (
          'type' => 'varchar(20)',
          'label' => '卖家手机号码',
          'sdfpath' => 'seller/seller_phone',
          'editable' => false,
      ),
      'seller_zip' =>
      array (
          'type' => 'varchar(10)',
          'label' => '卖家的邮编',
          'sdfpath' => 'seller/seller_zip',
          'editable' => false,
      ),      
      'seller_area' =>
      array (
          'type' => 'region',
          'label' => '卖家所在地区',
          'sdfpath' => 'seller/seller_area',
          'editable' => false,
      ),  
      'seller_address' =>
      array (
          'type' => 'varchar(255)',
          'label' => '卖家详细地址',
          'sdfpath' => 'seller/seller_address',
          'editable' => false,
      ),     
      'print_status' =>
      array (
          'type' =>array (
                  0 => '否',
                  1 => '是'
          ),
          'default' => '0',
          'required' => true,
          'label' => ' 打印前端发货人信息',
      ),     
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
