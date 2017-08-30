<?php
$db['foreign_bn']=array (
  'columns' => 
  array (
    'outer_bn' => 
    array (
      'type' => 'varchar(50)',
      'pkey' => true,
	  'required' => true,
      'label' => '外部BN',
     ),
    'wms_id' => 
    array (
      'type' => 'varchar(32)',
      'pkey' => true,
      'label' => '来源WMS',
    ),
    'inner_bn' => 
    array (
      'type' => 'varchar(50)',
      'pkey' => true,
      'required' => true,
      'label' => '内部BN',
    ),
  ),
  'comment' => '外部编号关联表',
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);