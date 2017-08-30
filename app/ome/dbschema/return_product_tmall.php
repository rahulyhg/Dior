<?php
$db['return_product_tmall']=array (
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
   'contact_id' =>
    array (
      'type' => 'int',
      'label' => '收货地区',
      'width' => 170,
      'editable' => false,
      
    ),
   
   'operation_contraint'=>array(
        'type'=>'varchar(45)',
        'label'=>'支付宝交易号',
   
   ),
   'current_phase_timeout'=>array(
        'type'=>'time',
        'label'=>'当前状态超时时间',
   ),
    'refund_version'=>array (
      'type' => 'int unsigned',
      'label'=>'退款版本号',
      'editable' => false,
    ),
    'alipay_no'=>array(
        'type'=>'varchar(45)',
        'label'=>'支付宝交易号',
    ),
    'tag_list'=>array(
        'type'=>'longtext',
        'label'=>'退款标签',
    ),
    'cs_status'=>array(
        'label'=>'淘宝小二是否介入',
        'type' =>
      array(
         'yes'=>'是',
         'no'=>'否',
      ),
      'default' => 'no',
    ),
     
    'buyer_nick'=>array(
        'type' => 'varchar(50)',
        'label'=>'买家昵称',
    ),
    'seller_nick'=>array(
         'type' => 'varchar(50)',
        'label'=>'卖家昵称',
    ),
    'trade_status'=>array(
        'type'=>array(
            'wait_send_good'=>'等待卖家发货',
            'wait_confirm_good'=>'卖家已发货',
            'finish'=>'交易完成',
        ),

    ),
    'refund_version'=>array (
      'type' => 'varchar(50)',
      'label'=>'退款版本号',
      'editable' => false,
    ),
     'refund_phase'=>array(
        'type'=>array(
            'onsale'=>'售中',
            'aftersale'=>'售后',
        
        ),
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
    'refund_type'=>array(
        'type'=>array(
            'refund'=>'退款单',
            'return'=>'退货单',
        ),
        
       'default'=>'return',
    ),
    'bill_type'=>array(
        'type'=>array(
            'refund_bill'=>'退款单',
            'return_bill'=>'退货单',
        ),
        'default'=>'return_bill',
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