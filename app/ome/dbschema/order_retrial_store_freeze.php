<?php
$db['order_retrial_store_freeze']=array (
  'columns' => 
   array (
    'sid' => 
        array (
            'type' => 'int unsigned',
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
            'required' => true,
            'label' => '编号',
			'order'=>0,
    ),
    'retrial_id' =>
        array (
            'type' => 'int(10)',
            'default' => '0',
            'required' => true,
            'label' => '复审ID',
            'in_list' => false,
            'default_in_list' => true,
            'width' => 60,
            'hidden' => true,
            'order'=>10,
    ),
    'order_id' =>
        array (
            'type' => 'int unsigned',
            'default' => '0',
            'required' => true,
            'in_list' => false,
            'label' => '订单ID',
            'width' => 100,
            'order'=>20,
    ),
    'edit_num' =>
        array (
            'type' => 'smallint(6)',
            'default' => '0',
            'required' => true,
            'label' => '编辑次数',
            'in_list' => false,
            'default_in_list' => true,
            'width' => 80,
            'hidden' => true,
            'order'=>30,
    ),
    'is_old' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => '是否原商品',
	  'order'=>40,
    ),
    'is_del' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => '是否删除状态',
	  'order'=>50,
    ),
    'status' =>
        array (
            'type' => array('add'=>'新增', 'edit'=>'编辑', 'delete'=>'删除', 'normal'=>'正常'),
            'default' => 'edit',
            'required' => true,
            'label' => '操作状态',
            'in_list' => false,
            'default_in_list' => true,
            'width' => 80,
            'hidden' => true,
            'order'=>55,
    ),
    'product_id' =>
        array (
            'type' => 'int unsigned',
            'default' => '0',
            'required' => true,
            'in_list' => false,
            'label' => '商品ID',
            'width' => 80,
            'order'=>60,
    ),
    'bn' =>
    array (
      'type' => 'varchar(30)',
      'label' => '货号',
      'width' => 150,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => false,
      'in_list' => false,
      'default_in_list' => false,
	  'order'=>65,
    ),
    'buy_num' =>
    array (
      'type' => 'number',
      'default' => '0',
      'required' => true,
      'in_list' => false,
      'default_in_list' => false,
      'label' => '购买数量',
      'width' => 65,
	  'order'=>70,
    ),
    'up_num' =>
    array (
      'type' => 'number',
      'default' => '0',
      'required' => true,
      'in_list' => false,
      'default_in_list' => false,
      'label' => '上次购买量',
      'width' => 65,
	  'order'=>75,
    ),
    'diff_num' =>
    array (
      'type' => 'char(12)',
	  'default' => '0',
      'required' => true,
      'in_list' => false,
      'default_in_list' => false,
      'label' => '变化购买量',
      'width' => 65,
	  'order'=>80,
    ),
    'add_num' => 
    array (
      'type' => 'number',
      'default' => '0',
      'required' => true,
      'in_list' => false,
      'default_in_list' => false,
      'label' => '强制增加数量',
	  'width' => 80,
	  'order'=>90,
    ),
    'dateline' => array(
          'type' => 'time',
          'default' => '0',
          'required' => true,
          'label' => '添加日期',
          'filtertype' => 'time',
          'filterdefault' => true,
          'in_list' => true,
          'default_in_list' => true,
          'width' => 130,
          'order'=>95,
    ),
  ),
  'index' => array(
        'retrial_order_product' => array(
            'columns'=>array(
                0=>'retrial_id',
                1=>'order_id',
				2=>'product_id',
            ),
        ),
    ),
  'engine' => 'innodb',
  'version' => '$Rev: $',
);