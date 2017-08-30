<?php
$db['ome_rmatype']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
	  'filtertype' => 'normal',
    ),
    'problem_id' => 
    array (
      'type' => 'table:return_product_problem@ome',
	  'sdfpath' => 'problem/problem_id',
      'label' => '售后类型',
      'width' => 120,
      'searchtype' => 'head',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
	  'filtertype' => 'normal',
    ),
	'num' => 
    array (
      'type' => 'longtext',
      'label' => '售后单据数量',
      'width' => 110,
      'filtertype' => 'normal',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
	'shop_id' => 
    array (
	  'type' => 'table:shop@ome',
      'sdfpath' => 'shop/shop_id',
      'required' => false,
      'pkey' => true,
      'editable' => false,
	  'label' => '所属店铺',
    ),
	'createtime' =>
    array (
      'type' => 'time',
      'label' => '所属时间',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
  ),
  'comment' => '售后类型',
  'index' => 
  array (
    'ind_problem_id' => 
    array (
      'columns' => 
      array (
        0 => 'problem_id',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
