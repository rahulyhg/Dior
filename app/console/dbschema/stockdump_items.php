<?php
$db['stockdump_items']=array (
  'columns' =>
  array (
    'item_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'stockdump_id' =>
    array (
      'type' => 'table:stockdump',
      'required' => true,
      'label' => 'ID'
    ),
    'stockdump_bn' =>
    array (
      'type' => 'varchar(20)',
      'required' => true,
      'label' => '编号',
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'editable' => false,
    ),
   
   'stockdump_date' =>
    array (
      'type' => 'time',
      'label' => '生成时间',
    ),
    'bn' =>
    array (
      'type' => 'varchar(30)',
      'label' => '货号',
    ),
    'product_name' =>
    array (
      'type' => 'varchar(200)',
      'label' => '货品名称',
    ),
    'num' =>
    array (
      'type' => 'number',
      'label' => '数量',
	  'default' => 0,
    ),
    'in_nums' =>
    array (
      'type' => 'number',
      'label' => '已出入库数量',
	  'default' => 0,
    ),
   'defective_num' =>
    array (
      'type' => 'number',
      'label' => '不良品数量',
	  'default' => 0,
    ),
    'appro_price'=>
    array(
        'type' => 'money',
        'label' => '出入库价格',
    ),
  ),
  'index' => array(
        'idx_appropriation_bn' => array(
            'columns' => array('stockdump_bn','bn')
        ),
        
    ),
  'comment' => '库存出入库单明细',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
