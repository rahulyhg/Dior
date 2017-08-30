<?php
$db['adapter']=array (
  'comment' => '渠道适配器关系表',
  'columns' => 
  array (
    'channel_id' => 
    array (
      'type' => 'varchar(32)',
      'label' => '渠道ID',
      'required' => true,
      'pkey' => true,
    ),
    'adapter' =>
    array (
      'type' => 'varchar(32)',
      'label' => '渠道适配器',
    ),
    'config' => 
    array (
      'type' => 'longtext',
      'label'=> '应用及参数配置',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);