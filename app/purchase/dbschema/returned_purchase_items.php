<?php
$db['returned_purchase_items']=array (
  'columns' =>
  array (
    'item_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),

    'rp_id' =>
    array (
      'type' => 'table:returned_purchase',
      //'required' => true,
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
    ),
    'num' =>
    array (
      'type' => 'number',
      'required' => true,
      'editable' => false,
    ),
    'price' =>
    array (
      'type' => 'money',
      'required' => true,
      'editable' => false,
    ),
    'bn' =>
    array (
      'type' => 'varchar(30)',
      'label' => '货号',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'barcode' =>
    array (
      'type' => 'varchar(32)',
      'label' => '条形码',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'label' => '商品名称',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'spec_info' =>
    array (
      'type' => 'longtext',
      'label' => '货品描述',
      'width' => 110,
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'out_num' =>
    array (
      'type' => 'number',
      'editable' => false,
      'required' => true,
      'default' => 0,
      'label' => '出库数量',
    ),


  ),
  'comment' => '退货单明细',
  'engine' => 'innodb',
  'version' => '$Rev: 51996',
);
