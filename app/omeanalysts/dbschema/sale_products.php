<?php
$db['sale_products']=array (
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
	'product_id' =>  
    array (
      'type' => 'table:products@ome',
      'label' => '货号',
    ),
    'branch_id' =>  
    array (
      'type' => 'table:branch@ome',
      'label' => '仓库',
    ),
    'sales_nums' =>
    array (
      'type' => 'number',
      'editable' => false,
	  'label' => '销量',
    ),
	'sales_price' =>
    array (
      'type' => 'money',
      'editable' => false,
	  'label' => '销售单价',
    ),
	'sales_time' =>
    array (
      'type' => 'time',
      'label' => '销售时间',
    ),
  ),
  'comment' => '已销售产品',
  'index' => 
  array (
    'ind_product_id' => 
    array (
      'columns' => 
      array (
        0 => 'product_id',
      ),
    ),
    'ind_branch_id' => 
    array (
      'columns' => 
      array (
        0 => 'branch_id',
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
