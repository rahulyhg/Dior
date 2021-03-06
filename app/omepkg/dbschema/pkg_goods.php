<?php
$db['pkg_goods']=array (
  'columns' => 
  array (
    'goods_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'hidden' => true,
      'editable' => false,
    ),
    'pkg_bn' => 
    array (
      'type' => 'varchar(200)',
      'label' => '捆绑商品货号',
      'searchtype' => 'has',
      //'width' => 120,
      //'searchtype' => 'head',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      'label' => '商品名称',
      'is_title' => true,
      'default_in_list' => true,
      'width' => 260,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'custom',
      'filterdefalut' => true,
      'filtercustom' => 
      array (
        'has' => '包含',
        'tequal' => '等于',
        'head' => '开头等于',
        'foot' => '结尾等于',
      ),
      'in_list' => true,
    ),
//    'cat_id' => 
//    array (
//      'type'=>'mediumint(8) unsigned',
//      'required' => true,
//      'sdfpath' => 'category/cat_id',
//      'default' => 0,
//      'label' => '分类',
//      'width' => 75,
//      'editable' => false,
//      'filtertype' => 'yes',
//      'filterdefalut' => true,
//      'searchtype' => 'has',
//      //'in_list' => true,
//    ),
//    'type_id' => 
//    array (
//      'type'=>'mediumint(8) unsigned',
//      'sdfpath' => 'type/type_id',
//      'label' => '类型',
//      'width' => 75,
//      'editable' => false,
//      'filterdefalut' => true,
//      'searchtype' => 'has',
//      'filtertype' => 'yes',
//      'in_list' => true,
//      'default_in_list' => true,
//    ),
//    'goods_type' => 
//    array (
//      'type' => 
//      array (
//        'normal' => '普通商品',
//        'bind' => '捆绑商品',
//      ),
//      'sdfpath' => 'goods_type',
//      'default' => 'normal',
//      'required' => true,
//      //'label' => '销售类型',
//      'width' => 110,
//      'editable' => false,
//      'hidden' => true,
//      'filterdefault' => true,
//      //'searchtype' => 'has',
//      'filtertype' => 'yes',
//      //'in_list' => true,
//    ),
//    'brand_id' => 
//    array (
//      'type' => 'table:brand',
//      'sdfpath' => 'brand/brand_id',
//      'label' => '品牌',
//      'width' => 75,
//      'editable' => false,
//      'hidden' => true,
//      'in_list' => true,
//      'default_in_list' => true,
//      'filtertype' => 'yes',
//      'filterdefault' => true,
//    ),
//    'brief' => 
//    array (
//      'type' => 'varchar(255)',
//      'label' => '商品简介',
//      'width' => 110,
//      'hidden' => false,
//      'editable' => false,
//      'in_list' => true,
//      'filtertype' => 'normal',
//    ),
//    'intro' => 
//    array (
//      'type' => 'longtext',
//      'sdfpath' => 'description',
//      'label' => '详细介绍',
//      'width' => 110,
//      'hidden' => true,
//      'editable' => false,
//      'in_list' => true,
//    ),
//    'barcode' => 
//    array (
//      'type' => 'varchar(32)',
//      'label' => '条形码',
//      'width' => 110,
//      'hidden' => false,
//      'editable' => false,
//      'filtertype' => 'normal',
//      'in_list' => true,
//      'filterdefault' => true,
//      'searchtype' => 'has',  
//    ),
//    'mktprice' => 
//    array (
//      'type' => 'money',
//      'sdfpath' => 'product[default]/price/mktprice/price',
//      'label' => '市场价',
//      'width' => 60,
//      'editable' => false,
//    ),
//    'cost' => 
//    array (
//      'type' => 'money',
//      'sdfpath' => 'product[default]/price/cost/price',
//      'default' => '0',
//      'label' => '成本价',
//      'width' => 60,
//      'editable' => false,
//      'filtertype' => 'yes',
//      'filterdefault' => true,
//      'in_list' => true,
//    ),
//    'price' => 
//    array (
//      'type' => 'money',
//      'sdfpath' => 'product[default]/price/price/price',
//      'default' => '0',
//      'label' => '销售价',
//      'width' => 60,
//      'editable' => false,
//     
//    ),
//    'marketable' => 
//    array (
//      'type' => 'bool',
//      'default' => 'true',
//      'sdfpath' => 'status',
//      'required' => true,
//      'label' => '上架',
//      'width' => 30,
//      'editable' => false,
//      'filtertype' => 'yes',
//      'in_list' => true,
//    ),
    'weight' => 
    array (
      'type' => 'decimal(20,3)',
      //'sdfpath' => 'product[default]/weight',
      'label' => '重量',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
    ),
//    'unit' => 
//    array (
//      'type' => 'varchar(20)',
//      'sdfpath' => 'unit',
//      'label' => '单位',
//      'width' => 30,
//      'editable' => false,
//      'filtertype' => 'normal',
//      'in_list' => true,
//    ),
//    'score_setting' => 
//    array (
//      'type' => 
//      array (
//        'percent' => '百分比',
//        'number' => '实际值',
//      ),
//      'default' => 'number',
//      'editable' => false,
//    ),
//    'score' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'gain_score',
//      'label' => '积分',
//      'width' => 30,
//      'editable' => false,
//      'in_list' => true,
//    ),
//    'spec_desc' => 
//    array (
//      'type' => 'serialize',
//      'label' => '物品',
//      'width' => 110,
//     
//      'editable' => false,
//     
//    ),
//    'params' => 
//    array (
//      'type' => 'serialize',
//      'editable' => false,
//    ),
//    'uptime' => 
//    array (
//      'type' => 'time',
//      'depend_col' => 'marketable:true:now',
//      'label' => '上架时间',
//      'width' => 110,
//      'editable' => false,
//      'in_list' => true,
//      'filtertype' => 'yes',
//      'filterdefault' => true,
//    ),
//    'downtime' => 
//    array (
//      'type' => 'time',
//      'depend_col' => 'marketable:false:now',
//      'label' => '下架时间',
//      'width' => 110,
//      'editable' => false,
//      'in_list' => true,
//      'filtertype' => 'yes',
//      'filterdefault' => true,
//    ),
//    'last_modify' => 
//    array (
//      'type' => 'last_modify',
//      'label' => '更新时间',
//      'width' => 110,
//      'editable' => false,
//      'in_list' => true,
//      'filtertype' => 'yes',
//      'filterdefault' => true,
//    ),
//    'disabled' => 
//    array (
//      'type' => 'bool',
//      'default' => 'false',
//      'required' => true,
//      'editable' => false,
//    ),
//    'p_order' => 
//    array (
//      'type' => 'number',
//      'default' => 30,
//      'required' => true,
//      'label' => '排序',
//      'width' => 110,
//      'editable' => false,
//      'hidden' => true,
//    ),
//    'd_order' => 
//    array (
//      'type' => 'number',
//      'default' => 30,
//      'required' => true,
//      'label' => '排序',
//      'width' => 30,
//      'editable' => false,
//    ),
//    'p_1' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_1/value',
//      'editable' => false,
//    ),
//    'p_2' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_2/value',
//      'editable' => false,
//    ),
//    'p_3' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_3/value',
//      'editable' => false,
//    ),
//    'p_4' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_4/value',
//      'editable' => false,
//    ),
//    'p_5' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_5/value',
//      'editable' => false,
//    ),
//    'p_6' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_6/value',
//      'editable' => false,
//    ),
//    'p_7' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_7/value',
//      'editable' => false,
//    ),
//    'p_8' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_8/value',
//      'editable' => false,
//    ),
//    'p_9' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_9/value',
//      'editable' => false,
//    ),
//    'p_10' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_10/value',
//      'editable' => false,
//    ),
//    'p_11' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_11/value',
//      'editable' => false,
//    ),
//    'p_12' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_12/value',
//      'editable' => false,
//    ),
//    'p_13' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_13/value',
//      'editable' => false,
//    ),
//    'p_14' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_14/value',
//      'editable' => false,
//    ),
//    'p_15' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_15/value',
//      'editable' => false,
//    ),
//    'p_16' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_16/value',
//      'editable' => false,
//    ),
//    'p_17' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_17/value',
//      'editable' => false,
//    ),
//    'p_18' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_18/value',
//      'editable' => false,
//    ),
//    'p_19' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_19/value',
//      'editable' => false,
//    ),
//    'p_20' => 
//    array (
//      'type' => 'number',
//      'sdfpath' => 'props/p_20/value',
//      'editable' => false,
//    ),
//    'p_21' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_21/value',
//      'editable' => false,
//    ),
//    'p_22' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_22/value',
//      'editable' => false,
//    ),
//    'p_23' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_23/value',
//      'editable' => false,
//    ),
//    'p_24' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_24/value',
//      'editable' => false,
//    ),
//    'p_25' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_25/value',
//      'editable' => false,
//    ),
//    'p_26' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_26/value',
//      'editable' => false,
//    ),
//    'p_27' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_27/value',
//      'editable' => false,
//    ),
//    'p_28' => 
//    array (
//      'type' => 'varchar(255)',
//      'sdfpath' => 'props/p_28/value',
//      'editable' => false,
//    ),
//  ),
//  'comment' => '商品表',
//  'index' => 
//  array (
//    'uni_bn' => 
//    array (
//      'columns' => 
//      array (
//        0 => 'bn',
//      ),
//      'prefix' => 'UNIQUE',
//    ),
//    'ind_type_id' =>
//    array (
//        'columns' =>
//        array (
//          0 => 'type_id',
//        ),
//    ),
//    'ind_brand_id' =>
//    array (
//        'columns' =>
//        array (
//          0 => 'brand_id',
//        ),
//    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
