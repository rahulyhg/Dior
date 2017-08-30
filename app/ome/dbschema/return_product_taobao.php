<?php
$db['return_product_taobao']=array (
  'columns' => 
  array (
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'pkey' => true,
      'required' => true,
      'width' => 75,
      'editable' => false,
      ),
    'return_id' => 
    array(
      'type' => 'table:return_product@ome',
      'pkey' => true,
      'required' => true,
      'editable' => false,
      'comment' => '售后ID',
    ),
    'return_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '退货记录流水号',
      'comment' => '退货记录流水号',
      'editable' => false,
     
    ),
  
    'reship_addr' =>
    array (
      'type' => 'varchar(100)',
      'label' => '收货地址',
      'width' => 180,
      'editable' => false,
      
    ),
   'reship_zip' =>
    array (
      'type' => 'varchar(20)',
      'label' => '邮编',
      'width' => 180,
      'editable' => false,
      
    ),
    'reship_name' =>
    array (
      'type' => 'varchar(50)',
      'label' => '联系人',
      'width' => 180,
      'editable' => false,
      
    ),
    'reship_phone' =>
    array (
      'type' => 'varchar(30)',
      'label' => '电话',
      'width' => 180,
      'editable' => false,
      
    ),
     'reship_mobile' =>
    array (
      'type' => 'varchar(30)',
      'label' => '手机',
      'width' => 180,
      'editable' => false,
      
    ),
   'shipping_type'=>array(
    'type'=>'varchar(45)',
    'label'=>'物流方式',

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
   'alipay_no'=>array(
    'type'=>'varchar(100)',
    'label'=>'支付单编号',
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
   'good_return_time'=>array(
    'type' => 'time',
      'label' => '退货时间',
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
    'online_memo'=>array(
        'type' => 'longtext',
        'label' => '线上留言凭证',
    ),
    
 ),
  'index' =>
  array (
    'ind_return_apply_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'return_id',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);