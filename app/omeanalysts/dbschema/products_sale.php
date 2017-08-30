<?php
$db['products_sale']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
    ),
    'shop_id' => 
    array (
	  'type' => 'table:shop@ome',
      'required' => false,
      'editable' => false,
	  'label' => '来源店铺',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 1,
      'width' => 130,
    ),
	'bn' =>
    array (
      'type' => 'varchar(60)',
      'editable' => false,
	  'label' => '货号',
      'in_list' => true,
      'default_in_list' => true,
	  'default' => 0,
      'order' => 3,
      'width' => 70,
    ),
	'name' =>
    array (
      'type' => 'varchar(200)',
      'editable' => false,
	  'label' => '货品名称(规格)',
      'in_list' => true,
      'default_in_list' => true,
	  'default' => 0,
      'order' => 4,
      'width' => 200,
    ),
    'sales_num' =>
    array (
      'type' => 'number',
      'editable' => false,
	  'label' => '销售量',
      'in_list' => true,
      'default_in_list' => true,
	  'default' => 0,
      'order' => 5,
      'width' => 70,
    ),
	'sales_amount' =>
    array (
      'type' => 'money',
      'editable' => false,
	  'label' => '销售额',
      'in_list' => true,
      'default_in_list' => true,
	  'default' => 0,
      'order' => 6,
      'width' => 80,
    ),
    'brand_id' =>  
    array (
      'type' => 'table:brand@ome',
      'label' => '品牌',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 7,
      'width' => 130,
    ),
	'sales_time' =>
    array (
      'type' => 'time',
      'label' => '销售时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
	  'default_in_list' => true,
      'order' => 8,
      'width' => 130,
    ),
  ),
  'comment' => '产品销售排行榜',
  'index' => 
  array (
    'ind_shop_id' => 
    array (
      'columns' => 
      array (
        0 => 'shop_id',
      ),
    ),
    'ind_bnsalestime' => 
    array (
      'columns' => 
      array (
        0 => 'bn',
		1 => 'sales_time',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
