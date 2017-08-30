<?php
$db['return_product_problem']=array (
  'columns' => 
  array (
    'problem_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'label' => '类型自增ID',
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'problem_name' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'is_title' => true,
      'default' => '',
      'label' => '名称',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),  
    'disabled' => 
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'label' => '是否屏蔽',
      'comment' => '是否屏蔽（true：是；false：否）',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
  ),
  'index' => 
  array (
    'ind_disabled' => 
    array (
      'columns' => 
      array (
        0 => 'disabled',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment' => '售后问题类型表',
);