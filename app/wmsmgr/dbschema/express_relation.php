<?php
$db['express_relation']=array (
  'columns' =>
  array (
    'wms_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      //'extra' => 'auto_increment',
    ),
    'sys_express_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'editable' => false,
      'pkey' => true,
      'label' => '物流公司编号',
      'comment' => '物流公司编号',
      'width' =>140,
    ),
    'wms_express_bn' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
      'label' => 'WMS物流公司编号',
      'comment' => 'WMS物流公司编号',
      'width' =>140,
    ),
  ),
  'index' =>
  array (
    'index_wms_id_sys_express_bn' =>
    array (
      'columns' =>
      array (
        0 => 'wms_id',
        1 => 'sys_express_bn',
      ),
    ),
    
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);