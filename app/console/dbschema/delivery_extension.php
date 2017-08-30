<?php
$db['delivery_extension']=array (
 'columns' =>
  array (
    'delivery_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '发货单号',
      'comment' => '发货单号',
      'editable' => false,
    ),
    'original_delivery_bn' =>
    array (
      'type' => 'varchar(80)',
      'required' => true,
      'label' => '外部发货单号',
      'editable' => false,
        ),
    ),
     'index' =>
  array (
    'index_delivery_bn' =>
    array (
      'columns' =>
      array (
        0 => 'delivery_bn',
        1 => 'original_delivery_bn',
      ),
    ),
   
  ),
'engine' => 'innodb',
'version' => '$Rev: 41996 $',
);
?>