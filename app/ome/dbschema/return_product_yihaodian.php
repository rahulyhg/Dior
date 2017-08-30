<?php
$db['return_product_yihaodian']=array (
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

   'isdeliveryfee' =>
    array (
       'type' => 
      array (
        0 => '不退运费',
        1 => '退运费',
      
      ),
      'default'=>'0',
      'label' => '是否退运费',
      'width' => 170,
      'editable' => false,
      
    ),
    'sendbacktype' =>
    array (
       'type' => 
      array (
        0 => '不寄回',
        1 => '寄回',
      
      ),
       'default'=>'0',
      'label' => '是否寄回',
      'width' => 180,
      'editable' => false,
      
    ),
   'isdefaultcontactname' =>
    array (
      'type' => 
      array (
        0 => '不使用',
        1 => '使用',
      
      ),
       'default'=>'0',
      'label' => '是否使用默认联系人',
      'width' => 180,
      'editable' => false,
      
    ),
    'contactname' =>
    array (
      'type' => 'varchar(50)',
      'label' => '联系人名称',
      'width' => 180,
      'editable' => false,
      
    ),

    'contactphone' =>
    array (
      'type' => 'varchar(30)',
      'label' => '联系人电话',
      'width' => 180,
      'editable' => false,
      
    ),
     'sendbackaddress' =>
    array (
      'type' => 'varchar(150)',
      'label' => '联系人地址',
      'width' => 180,
      'editable' => false,
      
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