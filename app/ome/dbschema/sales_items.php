<?php

$db['sales_items']=array (
  'columns' =>
  array (
    'item_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'sale_id' =>
    array (
      'type' => 'table:sales@ome',
      'required' => true,
      'comment' => '销售单编号id',
    ),
    'product_id' =>
    array (
      'type' => 'table:products@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => '货品ID',
    ),
    'bn' =>
    array (
      'type' => 'varchar(40)',
      'required' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'default_in_list'=>true,
      'in_list'=>true,
      'label' => '货号',
      'comment' => '货号',
    ),
    'name'=>
    array(
      'type'=>'varchar(255)',
      'default'=>'',
      'comment' => '商品名称',
      'label' => '商品名称',
    ),
    'pmt_price' =>
    array (
      'type' => 'money',
      'default' => '0',
      'editable' => false,
      'label' => '商品优惠价',
      'comment' => '商品优惠价',
    ),
    'orginal_price' =>
    array (
      'type' => 'money',
      'default' => 0,
      'label' => '商品原始价格',
      'comment' => '商品原始价格',
    ),
    'price' =>
    array (
      'type' => 'money',
      'default' => 0,
      'label' => '销售价格',
      'comment' => '销售价格',
    ),
    'spec_name' =>
    array(
      'type' => 'varchar(255)',
      'default' => '',
      'label' => '商品规格',
      'comment' => '商品规格',
    ),
    'nums' =>
    array (
      'type' => 'mediumint',
      'required' => true,
      'comment' => '销售数量',
    ),
    'sales_amount' =>
    array (
      'type' => 'money',
      'default' => '0',
      'filterdefault' => true,
      'default_in_list'=>true,
      'in_list'=>true,
      'label' => '销售额',
      'comment' => '正销售:商品在订单实际成交金额；（原始金额-优惠金额-其他费用）;负销售:退款金额',
      'order' => '14'
    ),
    'sale_price' =>
        array (
      'type' => 'money',
      'default' => '0',
      'label' => '订单明细货品销售价',
    ),
    'apportion_pmt' =>
        array (
      'type' => 'money',
      'default' => '0',
      'label' => '平摊优惠金额',
    ),
    'cost' =>
    array (
      'type' => 'money',
      'default' => 0,
      'comment' => '成本价格',
    ),
    'cost_amount' =>
    array (
      'type' => 'money',
      'default' => 0,
      'label' => '成本金额',
      'filterdefault' => true,
      'default_in_list' => true,
      'comment' => '数量*成本单价',
    ),
  'gross_sales' =>
    array (
      'type' => 'money',
      'default' => 0,
      'label' => '销售毛利',
      'comment' => '商品的销售毛利',
      'in_list'=>true,
    ),
    'gross_sales_rate' =>
    array (
      'type' => 'decimal(10,2)',
      'default' => 0,
      'label' => '毛利率',
      'comment' => '商品的毛利率',
      'in_list'=>true,
    ),
    'cost_tax' =>
    array (
      'type' => 'money',
      'comment' => '税率',
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'comment' => '仓库名称',
    ),
    'iostock_id' =>
    array (
      'type' => 'table:iostock@ome',
      'comment' => '出入库单号',
    ),
    'sell_code' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '销售编码',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);