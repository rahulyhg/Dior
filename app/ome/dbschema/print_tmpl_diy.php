<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$db['print_tmpl_diy']=array (
  'columns' => 
  array (
    'tmpl_name' => array (
       'type' => 'varchar(50)',
       'pkey' => true,
       'required' => true,
    ),
    'app' => array (
       'type' => 'varchar(20)',
       'required' => true,
       'default' => 'ome',
       'editable' => false,
       'pkey' => true, 
    ),
    'content' => array(
        'type'=>'longtext',
        'label' =>app::get('ome')->_('内容'),
        'default' => 0,
    ),
    'edittime' => array (
      'type' => 'int(10) ',
      'required' => true,
    ),
    'active' => array(
        'type'=>"enum('true', 'false')",
        'default' => 'true',      
    ),
   
  ),   
  'comment' => app::get('ome')->_('信息表'),
   'engine' => 'innodb',
   'version' => '$Rev$',
);
