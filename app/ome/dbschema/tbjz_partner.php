<?php
$db['tbjz_partner']=array (
  'columns' =>
  array (
   'order_id' =>
    array (
      'type' => 'table:orders@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
    'tp_code' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label'=>'物流商编码',
    ),
     'tp_name' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '物流商名称',
    ),
    'service_type' => 
    array (
      'type' => 'varchar(50)',
      'default' => 0,
      'editable' => false,
      'label' => '服务类型',
    ),
     'is_virtual_tp' => 
    array (
      'type' => 'bool',
      'default' => 'false',
      'label' => '是否虚拟物流商',
    ),
  ),
  
  'comment' => '淘宝家装服务商信息表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
