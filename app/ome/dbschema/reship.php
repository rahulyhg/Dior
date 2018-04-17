<?php
$db['reship']=array (
  'columns' =>
  array (
    'reship_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
      'comment' => '退换货ID',
    ),
    'problem_id' =>
    array(
      'type' => 'table:return_product_problem@ome',
      'label' => '问题类型ID',
    ),
    'reship_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '退换货单号',
      'comment' => '退换货单号',
      'editable' => false,
      'searchtype' => 'has',
      'filterdefault' => true,
      'filtertype' => 'yes',
      'width' =>200,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
	'm_reship_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => false,
      'label' => '前端退换货单号',
      'comment' => '前端退换货单号',
      'editable' => false,
      'searchtype' => 'has',
      'filterdefault' => true,
      'filtertype' => 'yes',
      'width' =>200,
      'in_list' => true,
      'default_in_list' => false,
      'is_title' => true,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'label' => '订单号',
      'comment' => '订单号',
      'editable' => false,
      'width' =>200,
      'in_list' => false,
      'default_in_list' => false,
    ),
	'p_order_id' =>
    array (
      'type' => 'table:orders@ome',
      'label' => '换货单相应订单号',
      'comment' => '换货单相应订单号',
      'editable' => false,
      'width' =>200,
      'in_list' => false,
      'default_in_list' => false,
    ),
    'money' =>
    array (
      'type' => 'money',
      'required' => true,
      'default' => 0,
      'label' => '物流费用',
      'comment' => '配送费用',
      'editable' => false,
      'filtertype' => 'number',
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'is_protect' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'label' => '是否保价',
      'comment' => '是否保价',
      'editable' => false,
      'filtertype' => 'yes',
      'in_list' => true,
    ),
    'delivery' =>
    array (
      'type' => 'varchar(20)',
      'label' => '配送方式',
      'comment' => '配送方式(货到付款、EMS...)',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>110,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'logi_id' =>
    array (
      'type' => 'varchar(50)',
      'comment' => '物流公司ID',
      'editable' => false,
      'label' => '物流公司ID',
      'in_list' => true,
    ),
    'logi_name' =>
    array (
      'type' => 'varchar(100)',
      'label' => '物流公司',
      'comment' => '物流公司名称',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'logi_no' =>
    array (
      'type' => 'varchar(50)',
      'label' => '物流单号',
      'comment' => '物流单号',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>130,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'return_logi_name' =>
    array (
      'type' => 'varchar(100)',
      'label' => '退回物流公司',
      'comment' => '退回物流公司名称',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'return_logi_no' =>
    array (
      'type' => 'varchar(50)',
      'label' => '退回物流单号',
      'comment' => '退回物流单号',
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'width' =>130,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'ship_name' =>
    array (
      'type' => 'varchar(50)',
      'label' => '收货人',
      'comment' => '收货人姓名',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'ship_area' =>
    array (
      'type' => 'region',
      'label' => '收货地区',
      'comment' => '收货人地区',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'ship_addr' =>
    array (
      'type' => 'varchar(100)',
      'label' => '收货地址',
      'comment' => '收货人地址',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>150,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'ship_zip' =>
    array (
      'type' => 'varchar(20)',
      'label' => '收货邮编',
      'comment' => '收货人邮编',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'ship_tel' =>
    array (
      'type' => 'varchar(30)',
      'label' => '收货人电话',
      'comment' => '收货人电话',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'ship_mobile' =>
    array (
      'type' => 'varchar(50)',
      'label' => '收货人手机',
      'comment' => '收货人手机',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'ship_email' =>
    array (
      'type' => 'varchar(150)',
      'label' => '收货人Email',
      'comment' => '收货人Email',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    't_begin' =>
    array (
      'type' => 'time',
      'label' => '单据创建时间',
      'comment' => '单据生成时间',
      'editable' => false,
      'filtertype' => 'time',
      'in_list' => true,
    ),
    't_end' =>
    array (
      'type' => 'time',
      'comment' => '单据结束时间',
      'editable' => false,
      'label' => '单据结束时间',
      'in_list' => true,
    ),
    'op_id' =>
    array (
      'type' => 'table:account@pam',
      'label' => '操作员',
      'comment' => '操作者',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'status' =>
    array (
      'type' =>
      array (
        'succ' => '成功到达',
        'failed' => '发货失败',
        'cancel' => '已取消',
        'progress' => '运送中',
        'timeout' => '超时',
        'ready' => '准备发货',
        'stop' => '暂停',
        'back' => '打回',
      ),
      'default' => 'ready',
      'required' => true,
      'comment' => '状态',
      'editable' => false,
      'label' => '状态',
      'width' =>65,
      'in_list' => false,
      'default_in_list' => false,
    ),
    'is_check' =>
    array(
      'type' => array(
        0 => '未审核',
        1 => '审核成功',
        2 => '审核失败',
        3 => '收货成功',
        4 => '拒绝收货',
        5 => '拒绝',
        6 => '补差价',
        7 => '完成',
        8 => '质检通过',
        9 => '拒绝质检',
        10 => '质检异常',
        11 => '待确认',
        12=>'收货异常',
        13 => '质检中',
      ),
      'default'=>'0',
      'comment' => '当前状态',
      'editable' => false,
      'label' => '当前状态',
      'width' =>65,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'label' => '备注',
      'comment' => '备注',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
	'custom_mark' =>
    array (
      'type' => 'longtext',
      'label' => '客服留言',
      'comment' => '客服留言',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'tmoney' =>
    array (
      'type' => 'money',
      'editable' => false,
      'label' => '退款的金额',
      'in_list' => true,
    ),
    'bmoney' =>
    array (
      'type' => 'money',
      'editable' => false,
      'label' => '折旧(其他费用)',
      'in_list' => true,
    ),
    'totalmoney' =>
    array (
      'type' => 'money',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '最后合计金额',
      'width' => 130,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'comment' => '无效',
      'editable' => false,
      'label' => '无效',
      'in_list' => true,
    ),
    'return_id' =>
    array (
      'type' => 'table:return_product@ome',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filterdefault' => true,
      'filtertype' => 'yes',
      'label' => '售后申请单号',
    ),
    'reason' =>
    array (
      'type' => 'longtext',
      'editable' => false,
	  'label' => '收货/质检原因',
    ),
    'return_type' =>
    array (
      'type' =>
      array (
        'return' => '退货',
        'change' => '换货',
		'refuse' => '拒收退货',
      ),
      'default' => 'return',
      'required' => true,
      'comment' => '退换货状态',
      'editable' => false,
      'label' => '退换货状态',
      'width' =>65,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => true,
      'filterdefault' => true,
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
    ),
    'diff_order_bn' => array(
        'type' => 'varchar(32)',
        'label' => app::get('ome')->_('补差价订单'),
        'in_list' => true,
    ),
    'diff_money' => array(
        'type' => 'money',
        'label' => app::get('ome')->_('补差价费'),
        'in_list' => true,
        'default' => 0,
    ),
    'change_amount' => array(
      'type' => 'money',
      'label' => app::get('ome')->_('换出金额'),
      'in_list'=>true,
      'default' => 0,
    ),
    'cost_freight_money' => array(
        'type' => 'money',
        'label' => app::get('ome')->_('买家承担的邮费'),
        'in_list' => true,
        'default' => 0,
    ),
    'need_sv' => array(
        'type' => 'bool',
        'default' => 'true',
    ),
    'branch_id' =>
    array (
      'type' => 'number',
      'editable' => false,
      'label'=>'仓库ID',
      'comment'=>'仓库ID',
    ),    
    'bcmoney' => array(
        'type' => 'money',
        'label' => app::get('ome')->_('补偿费用'),
        'in_list' => true,
        'default' => 0,
    ),     
'source' =>
    array (
      'type' => 'varchar(50)',
      'default' => 'local',
      'editable' => false,
      'label'=>'来源',
      'in_list' => true,
    ),
     'outer_lastmodify' =>
    array (
      'label' => '数据推送的修改时间',
      'type' => 'time',
      'width' => 130,
      'editable' => false,

    ),
	'return_reason' =>
    array (
      'type' => 'varchar(32)',
      'label' => '退货原因',
      'is_title' => true,
      'width' => 125,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'out_iso_bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '外部单号',
      'is_title' => true,
      'width' => 125,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'archive' =>
    array (
    'type' => 'tinyint unsigned',
    'required' => true,
    'default' => 0,
    'editable' => false,
    ),
     'check_time' =>
    array (
      'type' => 'time',
      'comment' => '单据审核时间',
      'editable' => false,
      'label' => '单据审核时间',
      'in_list' => true,
    ),
	'qa_memo'=>array(
		'type' => 'longtext',
		'label' => '质检备注',
		'comment' => '质检备注',
		'editable' => false,
		'filtertype' => 'normal',
		'in_list' => true,
	),
	'image1'=>array(
		'type' => 'varchar(50)',
		'label' => '质检凭证',
		'comment' => '质检凭证',
		'editable' => false,
	),
	'image2'=>array(
		'type' => 'varchar(50)',
		'label' => '质检凭证',
		'comment' => '质检凭证',
		'editable' => false,
	),
	'image3'=>array(
		'type' => 'varchar(50)',
		'label' => '质检凭证',
		'comment' => '质检凭证',
		'editable' => false,
	),
  ),
  'comment' => '退换货单表',
  'index' =>
  array (
    'ind_diff_order_bn' => array(
        'columns' => array('diff_order_bn'),
    ),
    'ind_reship_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'reship_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_reship_bn' =>
    array (
        'columns' =>
        array (
          0 => 'reship_bn',
        ),
    ),
    'ind_t_begin' =>
    array (
        'columns' =>
        array (
          0 => 't_begin',
        ),
    ),
    'ind_logi_no' =>
    array (
      'columns' =>
      array (
        0 => 'logi_no',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);