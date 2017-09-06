<?php
$db['refund_apply']=array (
  'columns' => 
  array (
    'apply_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'refund_apply_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => '',
      'label' => '退款申请单号',
      'width' => 140,
      'editable' => false,
      'in_list' => true,
      'is_title' => true,
    ),
	  'alipaybatchno' =>
    array (
      'type' => 'varchar(200)',
      'required' => false,
      'default' => '',
      'label' => '支付宝退款批次号',
      //'width' => 140,
      'editable' => false,
   //   'in_list' => true,
    //  'is_title' => true,
    ),
	 'wxpaybatchno' =>
    array (
      'type' => 'varchar(200)',
      'required' => false,
      'default' => '',
      'label' => '微信退款批次号',
      //'width' => 140,
      'editable' => false,
   //   'in_list' => true,
    //  'is_title' => true,
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
      'label' => '订单号',
      'editable' => false,
    ),
	'wx_order_bn'=>array(
	  'type' => 'varchar(32)',
	  'is_title' => true,
	  'label' => 'WX系统订单号',
	  'editable' => false,
	  'filtertype' => 'normal',
	  'filterdefault' => true,
	  'in_list' => true,
	  'default_in_list' => true,
	),
	'reship_id' =>
    array (
      'type' => 'varchar(200)',
      'required' => false,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
      'label' => 'reship_id',
      'editable' => false,
    ),
    'pay_type' => 
    array (
      'type' => 
      array (
        'online' => '在线支付',
        'offline' => '线下支付',
        'deposit' => '预存款支付',
      ),
      'default' => 'online',
      'required' => true,
      'label' => '支付类型',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'account' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'label' => '退款帐号',
    ),
    'bank' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '退款银行',
    ),
	 'BeneficiaryName' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'label' => '收款人',
    ),
	 'BeneficiaryBankName' =>
    array (
      'type' => 'varchar(250)',
      'editable' => false,
      'in_list' => true,
      'label' => '收款人银行',
    ),
	 'BankName' =>
    array (
      'type' => 'varchar(250)',
      'editable' => false,
      'in_list' => true,
      'label' => '银行',
    ),
    'pay_account' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'label' => '收款帐号',
    ),
    'money' =>
    array (
      'type' => 'money',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '申请退款金额',
      'width' => '70',
    ),
    'refunded' =>
    array (
      'type' => 'money',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '已退款金额',
      'width' => '70',
    ),
    'payment' =>
    array (
      'type' => 'table:payment_cfg@ome',
      'editable' => false,
      'in_list' => true,
      'label' => '付款方式',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'memo' =>
    array (
      'type' => 'text',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
       'in_list' => true,
      'default_in_list' => true,
      'label' => '退款原因',
    ),
	  'apimsg' =>
    array (
      'type' => 'text',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
       'in_list' => true,
      'default_in_list' => true,
      'label' => '退款错误信息',
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '申请时间',
      'width' => 130,
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'last_modified' => 
    array (
      'label' => '最后更新时间',
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),
    'apply_op_id' =>
    array (
      'type' => 'table:account@pam',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '申请操作员',
    ),
    'verify_op_id' =>
    array (
      'type' => 'table:account@pam',
      'editable' => false,
    ),
    'status' =>
    array (
      'type' =>
      array (
        0 => '未审核',
        1 => '审核中',
        2 => '已接受申请',
        3 => '已拒绝',
        4 => '已退款',
        5 => '退款中',
        6 => '退款失败',
      ),
      'default' => '0',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '退款状态',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
	 'isk' =>
    array (
      'type' =>
      array (
        0 => '不是',
        1 => '是',
      ),
      'default' => '0',
	  'label' => '是否跨行',
    ),
	 'iss' =>
    array (
      'type' =>
      array (
        0 => '不是',
        1 => '是',
      ),
      'default' => '0',
	  'label' => '是否上海建行',
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'return_id' => 
    array(
      'type' => 'table:return_product@ome',
      //'required' => true,
      'editable' => false,
      'comment' => '售后ID',
    ),    
    'addon' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'refund_refer' =>
    array(
      'type' =>
      array(
         0=>'normal',
         1=>'aftersale',
      ),
      'default' => '0',
      'label' => '退款来源',            
    ),
    'bcmoney' => array(
        'type' => 'money',
        'label' => app::get('ome')->_('补偿费用'),
        'in_list' => true,
        'default' => 0,
    ),    'product_data' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'label'=>'售后申请商品',
    ),
    'source' =>
    array (
      'type' => 'varchar(50)',
      'default' => 'local',
      'editable' => false,
      'label' => '来源',
      'in_list' => true,
      'filterdefault' => true,
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
    ),
    'mark_text' =>
    array (
      'type' => 'longtext',
      'label' => '卖家备注',
      'editable' => false,
    ),
    'problem_id' =>
    array(
      'type' => 'table:return_product_problem@ome',
      'label' => '问题类型ID',
    ),
     'outer_lastmodify' =>
    array (
      'label' => '前端店铺最后更新时间',
      'type' => 'time',
      'width' => 130,
      'editable' => false,
    
    ),
	 'wxstatus' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
     // 'editable' => false,
    ),
    'archive' =>
    array (
      'type' => 'tinyint unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
  ),
  'index' =>
  array (
    'ind_refund_apply_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'refund_apply_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_refund_apply_bn' =>
    array (
        'columns' =>
        array (
          0 => 'refund_apply_bn',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);