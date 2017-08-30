<?php
$db['delivery_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'delivery_id' => 
    array (
      'type' => 'table:delivery@ome',
      'required' => true,
      'editable' => false,
      'label' => '发货单号',
      'comment' => '配送流水号',
      'width' =>140,
    ),
    'logi_id' => 
    array (
      'type' => 'table:dly_corp@ome',
      'comment' => '物流公司ID',
      'editable' => false,
      'label' => '物流公司',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'logi_name' => 
    array (
      'type' => 'varchar(100)',
      'label' => '物流公司',
      'comment' => '物流公司名称',
      'editable' => false,
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'logi_no' => 
    array (
      'type' => 'varchar(50)',
      'label' => '物流单号',
      'comment' => '物流单号',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'width' =>110,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'create_time' => 
    array (
      'type' => 'time',
      'label' => '创建时间',
      'comment' => '单据生成时间',
      'editable' => false,
      'filtertype' => 'time',
      'in_list' => true,
    ),
  ),
  'index' => 
  array (
    'index_logi_no' => 
    array (
      'columns' => 
      array (
        0 => 'logi_no',
      ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);