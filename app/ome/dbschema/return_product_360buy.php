<?php
$db['return_product_360buy']=array (
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
   'receive_state'=>array(
    'type'=>array(
        'BUYER_NOT_RECEIVED'=>'买家未收到货',
        'BUYER_RECEIVED'=>'买家已收到货',
        'BUYER_RETURNED_GOODS'=>'买家已退货',
    ),
    'label'=>'货物状态',
   ),
   'return_address'=>array(
     'type' => 'varchar(100)',
      'label' => '收货地址',
      
      'editable' => false,
   ),
   'send_type'=>array(
    'type'=>'varchar(45)',
    'label'=>'物流方式',
   
   ),
   'refuse_memo'=>array(
        'type' => 'longtext',
        'label' => '拒绝退款原因留言',
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