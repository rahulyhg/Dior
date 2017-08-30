<?php
$db['update_price']=array (
  'columns' => 
  array (
    'update_id' => 
    array (
      'type' => 'int(32)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'label' => '日志编号',
	  'extra' => 'auto_increment',
      'width' => 100,
    ),
    'product_bn' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'searchtype' => 'has',
      'label' => '货号',
      'width' => '150',
      'order' => '3',
    ),
    'start_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '同步时间',
      'width' => 350,
    ),
    'end_time' =>
    array (
      'type' =>'time',
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
      'editable' => false,
      'label' => '价格生效时间',
      'width' => 60,
    ),
    'price' =>
    array (
      'type' => 'money',
      'editable' => false,
      'required' => true,
      'label' => '价格',
      'in_list' => false,
    ),
    'discount_precent1' => 
    array (
      'type' => 'decimal(10,4)',
      'editable' => false,
    ),
    'discount_precent2' => 
    array (
      'type' => 'decimal(10,4)',
      'editable' => false,
    ),
    'discount_amount' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'status' => 
    array (
      'type' => 
        array (
          'hibernate' => '休眠中',
          'success' => '成功',
          'fail' => '失败',
        ),
      'required' => true,
      'default' => 'hibernate',
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'label' => '状态',
      'width' => 60,
    ),
    'retry' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'width' => 60,
      'edtiable' => false,
      'in_list' => true,
      'label' => '重试次数',
      'default_in_list' => true,
    ),
    'createtime' =>
    array (
      'type' => 'time',
      'label' => '记录创建时间',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      //'filtertype' => 'time',
      //'filterdefault' => true,
    ),
    'last_modified' =>
    array (
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
  ),
  'comment' => '商品价格更新',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
