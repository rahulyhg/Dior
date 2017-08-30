<?php
$db['print_tmpl']=array (
  'columns' =>
  array (
    'prt_tmpl_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => app::get('base')->_('ID'),
      'width' => 75,
      'editable' => false,
    ),
    'prt_tmpl_title' =>
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'default' => '',
      'label' => app::get('base')->_('模板名称'),
      'width' => 290,
      'unique' => true,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'shortcut' =>
    array (
      'type' => 'bool',
      'default' => 'true',
      'label' => app::get('base')->_('是否启用'),
      'width' => 110,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
    ),
    'prt_tmpl_width' =>
    array (
      'type' => 'tinyint unsigned',
      'default' => 100,
      'label' => app::get('base')->_('宽度'),
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'prt_tmpl_height' =>
    array (
      'type' => 'tinyint unsigned',
      'default' => 100,
      'label' => app::get('base')->_('高度'),
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'prt_tmpl_offsetx' =>
    array (
      'type' => 'tinyint',
      'default' => 0,
      'label' => app::get('base')->_('打印偏移(左)mm'),
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'prt_tmpl_offsety' =>
    array (
      'type' => 'tinyint',
      'default' => 0,
      'label' => app::get('base')->_('打印偏移(右)mm'),
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'prt_tmpl_data' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'file_id' =>
    array (
      'type' => 'number',
      'label' => app::get('base')->_('文件ID'),
      'width' => 75,
    ),
  ),
  'engine' => 'innodb',
);