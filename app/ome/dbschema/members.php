<?php
$db['members']=array (
  'columns' => 
  array (
    'member_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
	'm_memeber_num' => 
    array (
      'type' => 'varchar(50)',
      'label' => '前端用户编号',
      'width' => 75,
      'searchtype' => 'head',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
	'm_memeber_card' => 
    array (
      'type' => 'varchar(50)',
      'label' => '前端用户卡号',
      'width' => 75,
      'searchtype' => 'head',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'uname' => 
    array (
      'type' => 'varchar(50)',
      'label' => '用户名',
      'sdfpath' => 'account/uname',
      'is_title' => true,
      'width' => 75,
      'required' => 1,
      'searchtype' => 'head',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'name' => 
    array (
      'type' => 'varchar(50)',
      'label' => '姓名',
      'width' => 75,
      'sdfpath' => 'contact/name',
      'searchtype' => 'has',
      'editable' => true,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'lastname' => 
    array (
      'sdfpath' => 'contact/lastname',
      'type' => 'varchar(50)',
      'editable' => false,
    ),
    'firstname' => 
    array (
      'sdfpath' => 'contact/firstname',
      'type' => 'varchar(50)',
      'editable' => false,
    ),
    'password' => 
    array (
      'sdfpath' => 'account/password',
      'label' => '密码',
      'type' => 'password',
      'editable' => false,
      'in_list' => true,
    ),
    'area' => 
    array (
      'label' => '地区',
      'width' => 110,
      'type' => 'region',
      'sdfpath' => 'contact/area',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'addr' => 
    array (
      'type' => 'varchar(255)',
      'label' => '地址',
      'sdfpath' => 'contact/addr',
      'width' => 110,
      'editable' => true,
      'filtertype' => 'normal',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'mobile' => 
    array (
      'type' => 'varchar(30)',
      'label' => '手机',
      'width' => 75,
      'sdfpath' => 'contact/phone/mobile',
      'searchtype' => 'head',
      'editable' => true,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'tel' => 
    array (
      'type' => 'varchar(30)',
      'label' => '固定电话',
      'width' => 110,
      'sdfpath' => 'contact/phone/telephone',
      'searchtype' => 'head',
      'editable' => true,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'email' => 
    array (
      'type' => 'varchar(200)',
      'label' => 'EMAIL',
      'width' => 110,
      'sdfpath' => 'contact/email',
      'searchtype' => 'has',
      'editable' => true,
      'filtertype' => 'normal',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'zip' => 
    array (
      'type' => 'varchar(20)',
      'label' => '邮编',
      'width' => 110,
      'sdfpath' => 'contact/zipcode',
      'editable' => true,
      'filtertype' => 'normal',
      'in_list' => true,
    ),

    'order_num' => 
    array (
      'type' => 'number',
      'default' => 0,
      'label' => '订单数',
      'width' => 110,
      'editable' => false,
      'hidden' => true,
      'in_list' => true,
    ),
    'b_year' => 
    array (
        'label' => '生年',
      'type' => 'smallint unsigned',
      'width' => 30,
      'editable' => false,
      'in_list'=>false,
    ),
    'b_month' => 
    array (
      'label' => '生月',
      'type' => 'tinyint unsigned',
      'width' => 30,
      'editable' => false,
      'hidden' => true,
      'in_list' => false,
    ),
    'b_day' => 
    array (
      'label' => '生日',
      'type' => 'tinyint unsigned',
      'width' => 30,
      'editable' => false,
      'hidden' => true,
      'in_list' => false,
    ),
    'sex' => 
    array (
      'type' => 
      array (
        'female' => '女',
        'male' => '男',
      ),
      'sdfpath' => 'profile/gender',
      'default' => 'female',
      'required' => true,
      'label' => '性别',
      'width' => 30,
      'editable' => true,
      'filtertype' => 'yes',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'addon' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'wedlock' => 
    array (
      'type' => 'intbool',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
    'education' => 
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'vocation' => 
    array (
      'type' => 'varchar(50)',
      'editable' => false,
    ),
    'interest' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'regtime' => 
    array (
      'label' => '注册时间',
      'width' => 75,
      'type' => 'time',
      'editable' => false,
      'filtertype' => 'number',
      'filterdefault' => 'true',
      'in_list' => true,
      'default_in_list' => true,
    ),
    'state' => 
    array (
      'type' => 'tinyint(1)',
      'default' => 0,
      'required' => true,
      'label' => '验证状态',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'pay_time' => 
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'pw_answer' => 
    array (
      'label' => '回答',
      'type' => 'varchar(250)',
      'sdfpath' => 'account/pw_answer',
      'editable' => false,
    ),
    'pw_question' => 
    array (
      'label' => '安全问题',
      'type' => 'varchar(250)',
      'sdfpath' => 'account/pw_question',
      'editable' => false,
    ),
    'custom' => 
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'cur' => 
    array (
      'sdfpath' => 'currency',
      'type' => 'varchar(20)',
      'label' => '货币',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'lang' => 
    array (
      'type' => 'varchar(20)',
      'label' => '语言',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'unreadmsg' => 
    array (
      'type' => 'smallint unsigned',
      'default' => 0,
      'required' => true,
      'label' => '未读信息',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'number',
      'in_list' => true,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
    ),
    'remark' => 
    array (
      'label' => '备注',
      'type' => 'text',
      'width' => 75,
      'in_list' => true,
    ),
    'is_offical_member' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '官网用户',
    ),
    'is_customer' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'in_list' => true,
      'label' => '顾客',
    ),
  ),
  'index' => 
  array (
    'ind_email' => 
    array (
      'columns' => 
      array (
        0 => 'email',
      ),
    ),
    'uni_user' => 
    array (
      'columns' => 
      array (
        0 => 'uname',
      ),
    ),
    'ind_regtime' => 
    array (
      'columns' => 
      array (
        0 => 'regtime',
      ),
    ),
    'ind_disabled' => 
    array (
      'columns' => 
      array (
        0 => 'disabled',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
