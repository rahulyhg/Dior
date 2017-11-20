<?php
$db['logs']=array (
  'columns' => 
  array (
     'log_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'order_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => false,
      'default' => '',
      'label' => '订单号',
      'width' => 140,
      'editable' => false,
	  'searchtype' => 'has',
      'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
	  'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	'code'=>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
      'label' => '卡券Code码',
      'width' => 140,
      'editable' => false,
	  'searchtype' => 'has',
      'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
	  'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	'open_id'=>
    array (
      'type' => 'varchar(100)',
      'required' => false,
      'default' => '',
      'label' => 'open_id',
      'width' => 80,
      'editable' => false,
	  'searchtype' => 'has',
      'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
	  'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	'status' =>
    array (
      'type' =>
      array (
        'succ' => '成功',
        'fail' => '失败',
      ),
      'default' => 'fail',
      'required' => true,
      'label' => '接口状态',
      'width' => 75,
      'in_list' => true,
      'default_in_list' => true,
	  'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	'api_type' =>
    array (
      'type' =>
      array (
        'request' => '请求',
        'response' => '响应',
      ),
      'default' => 'request',
      'required' => true,
      'label' => '接口类型',
      'width' => 75,
      'in_list' => true,
      'default_in_list' => true,
	  'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	'api_method' =>
    array (
      'type' =>
      array (
        'get' => '获取订单',
		'search' => '查询订单',
		'conusme' => '核销礼品卡',
        'code_get' => '查询礼品卡信息',
		'accesstoken' => '获取JingToken',
		'wchattoken' => '获取微信Token',
		'getOrderId' => '抓取礼品卡事件',
		'acceptcard' => '领取礼品卡事件',
		'usergetcard' => '转赠礼品卡事件',
		'exchangeOrder'=>'兑换订单',
		'template'=>'消息模板',
		'refund'=>'礼品卡退款',
		'validate'=>'Pos查询',
		'redeem'=>'Pos核销',
      ),
      'default' => 'get',
      'required' => true,
      'label' => '接口方法',
      'width' => 75,
      'in_list' => true,
      'default_in_list' => true,
	  'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	 'createtime' =>
    array (
      'type' => 'time',
      'label' => '接口时间',
      'width' => 130,
      'editable' => false,
    //  'filtertype' => 'time',
    //  'filterdefault' => true,
	'default_in_list' => true,
      'in_list' => true,
	  'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	'request' =>
    array (
      'type' => 'longtext',
      'label' => '请求报文',
      'editable' => false,
	  'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
    ),
	'response' =>
    array (
      'type' => 'longtext',
      'label' => '响应报文',
      'editable' => false,
	  'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
    ),
	'msg' =>
    array (
      'type' => 'varchar(100)',
      'label' => '错误信息',
      'editable' => false,
	  'in_list' => true,
      'is_title' => true,
	  'default_in_list' => true,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);