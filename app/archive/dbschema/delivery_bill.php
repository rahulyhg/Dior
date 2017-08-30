<?php
$db['delivery_bill']=array (
  'columns' => 
  array (
    'logi_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
   
    ),
    'delivery_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'label' => '发货单号',
      'comment' => '配送流水号',
      'width' =>140,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),

    'logi_no' => 
    array (
      'type' => 'varchar(50)',
      'label' => '物流单号',
      'comment' => '物流单号',
      'editable' => false,
      'width' =>110,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
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