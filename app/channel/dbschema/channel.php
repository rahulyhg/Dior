<?php
$db['channel']=array (
  'columns' => 
  array (
    'channel_id' => 
    array (
      'type' => 'int unsigned',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false
    ),
    'channel_bn' => 
    array (
        'type' => 'varchar(100)',
        'required' => true,
        'label' => '应用编号',
        'editable' => false,
        
        'in_list' => true,
        'default_in_list' => true,
        'is_title' => true,
        'width' => '120',
    ),
    'channel_name' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'label' => '应用名称',
      'editable' => false,
      
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
      'width' => '120',
    ),
          
    'channel_type' =>
        array (
          'required' => false,
          'label' => '应用类型',
          'in_list' => true,
          'default_in_list' => true,
          'width' => '70',
           'type' => array (
               'crm' => 'crm',
               'wms' => 'wms',
               'shop' => 'shop',
               'wwgenius' => '旺旺精灵'
        )
      ),
    'config' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'crop_config' =>
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
    'last_download_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '上次下载订单时间(终端)',
      'in_list' => false,
      'default_in_list' => true,
    ),
    'last_upload_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '上次上传订单时间(ome)',
      'in_list' => false,
      'default_in_list' => true,
    ),
    'active' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'in_list' => false,
      'default_in_list' => true,
      'editable' => false,
      'label' => '激活',
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'last_store_sync_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '上次库存同步时间',
      'in_list' => false,
      'default_in_list' => true,
    ),
    'area' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'zip' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
    ),
    'addr' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'default_sender' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'mobile' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'tel' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'filter_bn' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    'bn_regular' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'express_remark' =>
    array (
      'type' => 'text',
      'editable' => false,
    ),
    'delivery_template' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'order_bland_template' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'node_id' =>
    array (
        'type' => 'varchar(32)',
        'editable' => false,
        'label' => '绑定状态',
        'editable' => false,
        'in_list' => true,
        'default_in_list' => true,
        'is_title' => true,
        'width' => '120', 
    ),
    'node_type' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
    ),
          
    'secret_key' =>
      array (
          'type' => 'varchar(100)',
          'editable' => false,
    ),
  'memo' =>
      array (
          'type' => 'varchar(255)',
          'editable' => false,
      ),
          
          
          
    'api_version' =>
    array (
      'type' => 'char(6)',
      'editable' => false,
    ),    
    'addon' =>
    array (
      'type' => 'serialize',
      'editable' => false,
    ),
  ),
  'index' =>
  array (
    'ind_node_id' =>
    array (
        'columns' =>
        array (
          0 => 'node_id',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);