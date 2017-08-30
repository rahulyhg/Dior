<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$db['network']=array (
  'columns' => 
  array (
    'node_id' => array (
      'type' => 'number',
      'label' => 'id',
      'required' => true,
      'width' => 100,
      'in_list' => true,
      'default_in_list' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
    ),
    'bind_url' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('base')->_('绑定网址'),
      'width' => 150,
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'callback_url' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('base')->_('回调地址'),
      'width' => 150,
     ),
    'license_url' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('base')->_('证书地址'),
      'width' => 150,
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'set' => 
    array (
      'type' => 
      array (
        'on' => '开启',
        'off' => '关闭',
        
      ),
      'default' => 'off',
      'width' => 100,
      'label' => '开启设置',
      'required' => true,
      'in_list' => true,
    ),
  
    'base_url' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('base')->_('请求PRISM地址'),
      'width' => 150,
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'editframe_url' => 
    array (
      'type' => 'varchar(100)',
      'label' => app::get('base')->_('自有体系统编辑订单URL:'),
      'width' => 150,
      'required' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'app_key' => 
    array (
      'type' => 'varchar(32)',
    
    ),
    'app_secret' => 
    array (
      'type' => 'varchar(32)',
   
    ),
  ),
  'version' => '$Rev: 41137 $',
  'ignore_cache' => true,
);