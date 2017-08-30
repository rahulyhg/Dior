<?php
$db['cat_sale_statis']=array (
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
    'type_id' =>  
    array (
      'type' => 'table:goods_type@ome',
      'label' => '商品类目',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 2,
      'width' => 130,
    ),
	'sales_num' =>
    array (
      'type' => 'number',
      'editable' => false,
	  'label' => '销售量',
	  'filtertype' => 'normal',
	  'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
	  'default' => 0,
      'order' => 3,
      'width' => 70,
    ),
	'sales_amount' =>
    array (
      'type' => 'money',
      'editable' => false,
	  'label' => '销售额',
	  'filtertype' => 'normal',
	  'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
	  'default' => 0,
      'order' => 4,
      'width' => 80,
    ),
    'brand_id' =>  
    array (
      'type' => 'table:brand@ome',
      'label' => '品牌',
      'width' => 75,
      'editable' => false,
    ),
	'sales_time' =>
    array (
      'type' => 'time',
      'label' => '销售时间',
      'width' => 130,
      'editable' => false,
    ),
  ),
  'comment' => '商品类目销售对比统计',
  'index' => 
  array (
    'ind_shop_id' => 
    array (
      'columns' => 
      array (
        0 => 'shop_id',
      ),
    ),
    'ind_type_id' => 
    array (
      'columns' => 
      array (
        0 => 'type_id',
      ),
    ),
    'ind_sales_time' => 
    array (
      'columns' => 
      array (
        0 => 'sales_time',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
