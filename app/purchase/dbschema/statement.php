<?php
$db['statement']=array (
  'columns' => 
  array (
    'statement_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => 'ID',
      'width' => 110,
      'editable' => false,
    ),
    'supplier_id' => 
    array (
      'type' => 'table:supplier',
      'required' => true,
      'label' => '供应商ID',
      'width' => 110,
      'editable' => false,
    ),
    'supplier_bn' => 
    array (
      'type' => 'varchar(32)',
      'label' => '供应商编号',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'object_bn' =>                  //TODO:单据bn，名字修正？
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'width' => 130,
      'label' => '单据号',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'statement_time' => 
    array (
      'type' => 'time',
      'label' => '结算时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'object_type' =>
    array (
      'type' => 
      array (
        1 => '赊购入库',
        2 => '现款结算',
        3 => '采购退货',
      ),
      'required' => true,
      'default' => 1,
      'editable' => false,
      'label' => '业务类型',
      'width' => 60,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'object_id' =>                  //TODO:单据id，名字修正？
    array (
      'type' => 'number',
      'editable' => false,
    ),
    'initial_pay' =>
    array (
      'type' => 'money',
      'comment' => '期初应付',
      'editable' => false,
      'width' => 60,
      'label' => '期初应付',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'pay_add' =>
    array (
      'type' => 'money',
      'comment' => '本期增加应付',
      'editable' => false,
      'width' => 80,
      'label' => '本期增加应付',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'paid' =>
    array (
      'type' => 'money',
      'comment' => '本期已付',
      'editable' => false,
      'width' => 60,
      'label' => '本期已付',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'final_pay' =>
    array (
      'type' => 'money',
      'comment' => '期末应付',
      'editable' => false,
      'width' => 60,
      'label' => '期末应付',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'initial_receive' =>
    array (
      'type' => 'money',
      'comment' => '期初应收',
      'editable' => false,
      'width' => 60,
      'label' => '期初应收',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'receive_add' =>
    array (
      'type' => 'money',
      'comment' => '本期增加应收',
      'editable' => false,
      'width' => 80,
      'label' => '本期增加应收',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'received' =>
    array (
      'type' => 'money',
      'comment' => '本期已收',
      'editable' => false,
      'width' => 60,
      'label' => '本期已收',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'final_receive' =>
    array (
      'type' => 'money',
      'comment' => '期末应收',
      'editable' => false,
      'width' => 60,
      'label' => '期末应收',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'difference' =>
    array (
      'type' => 'money',
      'comment' => '差额',
      'editable' => false,
      'width' => 60,
      'label' => '差额',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
  ),
  'index' => 
  array (
    'ind_object_id' => 
    array (
      'columns' => 
      array (
        0 => 'object_id',
      ),
    ),
     'ind_object_type' => 
    array (
      'columns' => 
      array (
        0 => 'object_type',
      ),
    ),
  ),
  'comment' => '结算单',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
