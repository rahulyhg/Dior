<?php
$db['refund_apply_taobao']=array (
  'columns' => 
  array (
    'apply_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
     
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
   
    'message_text' =>
    array (
      'type' => 'longtext',
      'label' => '留言凭证',
      'editable' => false,
    ),
    'refuse_memo'=>array(
        'type' => 'longtext',
        'label' => '拒绝退款原因留言',
    ),
   'oid' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '子订单号',
    ),
   
    'cs_status'=>array(
    'type' => array(
       
        1 => '不需客服介入',
        2 => '需要客服介入',
        3 => '客服已经介入',
        4 => '客服初审完成',
        5 => '客服主管复审失败',
        6 => '客服处理完成',
       
      ),
      'default'=>'1',
      'comment' => '客服介入状态',
      'editable' => false,
      'label' => '客服介入状态',
      'width' =>65,
      
   ),
   'advance_status'=>array(
    'type' => array(
        0=>'未申请状态',
        1 => '退款先行垫付申请中 ',
        2 => '退款先行垫付，垫付完成 ',
        3 => '退款先行垫付，卖家拒绝收货',
        4 => ' 退款先行垫付，垫付关闭',
        5 => '退款先行垫付，垫付分账成功',

       
      ),
      'default'=>'1',
     
      'editable' => false,
      'label' => '退款先行垫付',

   ),
   'split_taobao_fee'=>array(
    'type'=>'money',
    'label'=>'分账给第三方平台的钱',
   ),
   'split_seller_fee'=>array(
     'type'=>'money',
    'label'=>'分账给卖家的钱',
   ),
   
   'total_fee'=>array(
   'type'=>'money',
    'label'=>'交易总金额',
   ),
   'buyer_nick'=>array(
    'type'=>'varchar(50)',
    'label'=>'买家昵称',
   ),
   'seller_nick'=>array(
   'type'=>'varchar(50)',
    'label'=>'卖家昵称',
   ),
    'good_status'=>array(
    'type'=>array(
        'BUYER_NOT_RECEIVED'=>'买家未收到货',
        'BUYER_RECEIVED'=>'买家已收到货',
        'BUYER_RETURNED_GOODS'=>'买家已退货',
    ),
    'label'=>'货物状态',
   ),
   'has_good_return'=>array(
          'type' => 'bool',
      'default' => 'false',
         'label'=>'买家是否需要退货',
   ),
   'alipay_no'=>array(
    'type'=>'varchar(100)',
    'label'=>'支付单编号',
   ),
   'online_memo'=>array(
        'type' => 'longtext',
        'label' => '线上留言凭证',
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
          2=>'apply_id',
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