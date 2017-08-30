<?php
$db['pkg_product']=array (
  'columns' => 
  array (
  'product_id'=>
  array(
  'type'=>'mediumint(8)',
  ),
    'bn' => 
    array (
      'type' => 'varchar(200)',
      //'label' => '商品编号',
      //'width' => 120,
      //'searchtype' => 'head',
      //'editable' => false,
      //'filtertype' => 'yes',
      //'filterdefault' => true,
      //'in_list' => true,
      //'default_in_list' => true,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      ),
  'goods_id'=>
  array(
  'type'=>'table:pkg_goods',
  ),
//  'discount'=>
//  array(
//  'type'=>'decimal(5,3)',
//  'default' => NULL,
//  ),
  'pkgnum'=>
  array(
  'type'=>'mediumint(8)',
  'default' => 1,
  ),
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  );
?>
