<?php
$db['cards']=array (
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
    'order_id' => 
    array (
      'type' => 'table:orders@ome',
      'required' => false,
      'default' => 0,
      'editable' => false,
    ),
	'p_order_id' => 
    array (
      'type' => 'int unsigned',
      'required' => false,
      'default' => 0,
      'editable' => false,
    ),
	'p_order_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '购卡订单号',
      'is_title' => true,
      'width' => 125,
      'searchtype' => 'nequal',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'order_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '订单号',
      'is_title' => true,
      'width' => 125,
      'searchtype' => 'nequal',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'wx_order_bn'=>array(
	  'type' => 'varchar(32)',
	  'label' => 'WX系统订单号',
	  'is_title' => true,
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'searchtype' => 'nequal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
	),
	'status' =>
    array (
      'type' =>
      array (
        'normal' => '正常',
        'accept' => '已领取',
		'redeem' => '已核销',
      ),
      'default' => 'normal',
      'required' => true,
      'label' => '卡劵状态',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'card_type' =>
    array (
      'type' =>
      array (
        'online' => '线上',
        'offline' => '线下',
      ),
      'default' => 'online',
      'required' => true,
      'label' => '卡劵类型',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'convert_type' =>
    array (
      'type' =>
      array (
        'product' => '普通商品',
        'pkg' => '捆绑商品',
      ),
      'default' => 'product',
      'required' => true,
      'label' => '兑换类型',
	  'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'card_code'=>array(
	  'type' => 'varchar(32)',
	  'label' => '卡劵Code',
	  'is_title' => true,
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'searchtype' => 'nequal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
	),
	'old_card_code'=>array(
	  'type' => 'varchar(32)',
	  'label' => '原始卡劵Code',
	  'is_title' => true,
	  'width' => 125,
	  'editable' => false,
	  'filtertype' => 'normal',
	  'searchtype' => 'nequal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
	),
	'price' => 
    array (
      'type' => 'money',
      'default' => '0',
	  'label' => '价格',
      'editable' => false,
	  'in_list' => true,
	  'default_in_list' => true,
    ),
	'card_id'=>array(
	  'type' => 'varchar(35)',
	  'label' => '卡劵Id',
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	),
	'chatroom' =>
    array (
      'type' =>
      array (
        'false' => '否',
        'true' => '是',
      ),
      'default' => 'false',
      'required' => true,
      'label' => '是否发至群',
	  'in_list' => true,
    
    ),
	'form_id' =>
    array (
      'type' => 'varchar(40)',
      'editable' => false,
    ),
	'wechat_openid' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
    ),
	'begin_time' =>
    array (
      'type' => 'time',
	  'label' => '生效时间',
      'required' => false,
	  'filtertype' => 'time',
      'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
    ),
	'end_time' =>
    array (
      'type' => 'time',
	  'label' => '结束时间',
      'required' => false,
	  'filtertype' => 'time',
      'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
    ),
	'createtime' =>
    array (
      'type' => 'time',
	  'label' => '创建时间',
      'required' => false,
	  'filtertype' => 'time',
      'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
    ),
	'redeemtime' =>
    array (
      'type' => 'time',
	  'label' => '核销时间',
      'required' => false,
	  'filtertype' => 'time',
      'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
    ),
	'customer_code' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
	  'label'=>'POS Code',
	  'in_list' => true,
    ),
),
'index' =>
  array (
    'ind_order_bn' =>
    array (
        'columns' =>
        array (
          0 => 'order_bn',
        ),
    ),
	'ind_wx_order_bn' =>
    array (
        'columns' =>
        array (
          0 => 'wx_order_bn',
        ),
    ),
	'ind_card_code' =>
    array (
        'columns' =>
        array (
          0 => 'card_code',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);