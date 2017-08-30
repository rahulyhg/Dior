<?php
$db['branch_rule']=array (
  'columns' =>
  array (
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
      'label' => '仓库',
      'width' => 110,
      'pkey' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'type' =>
    array (
      'type' =>
      array (
        'custom' => '自定义',
        'other' => '复用',
        ),
      'default' => 'custom',
      'required' => true,
      'label' => '规则类型',
      'width' => 70,
      'editable' => false,

    ),
    'parent_id' =>
    array (
      'type' => 'bigint unsigned',
      'editable' => false,
      'default' => 0
    ),

     'last_modified' =>
    array (
      'label' => '最后更新时间',
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
    ),


),
 'index' => array (
 'ind_branch_id' =>
    array (
      'columns' =>
      array (
        0 => 'branch_id',
      ),
      'prefix' => 'unique',
    ),

 ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);