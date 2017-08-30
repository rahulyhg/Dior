<?php

$db['export_template']=array (
  'columns' => 
  array (
    'et_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'et_name' => 
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'default' => '',
      'label' => app::get('desktop')->_('导出模板名称'),
      'width' => 200,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'et_type' => 
    array (
      'type' => 'varchar(50)',
      'label' => app::get('desktop')->_('模板对象'),
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'et_filter' => 
    array (
      'type' => 'text',
      'required' => true,
      'default' => '',
      'label' => app::get('desktop')->_('模板条件'),
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
    ),
  ),
  'index' => 
  array (
    'ind_type' => 
    array (
      'columns' => 
      array (
        0 => 'et_type',
      ),
    ),
    'ind_name' => 
    array (
      'columns' => 
      array (
        0 => 'et_name',
      ),
    ),
  ),
  'version' => '$Rev: 42201 $',
);
