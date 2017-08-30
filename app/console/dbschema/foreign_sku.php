<?php
$db['foreign_sku']=array (
  'columns' => 
  array (
    'inner_sku' => 
    array (
      'type' => 'varchar(50)',
	  'required' => true,
      'label' => '货品编码',
      'comment'=>'内部sku',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
     ),
    'inner_product_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'label' => '货品ID',
      'width' => 110,
      'editable' => false,
    ),
    'wms_id' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '来源WMS',
      'editable' => false,
    ),
    'outer_sku' => 
    array (
      'type' => 'varchar(50)',
      'label' => '外部sku',
      'in_list' => true,
    ),
    'new_tag' =>
    array (
     'type' =>
      array (
        0 => '新品',
        1 => '非新品',
      ),
      'default' => '0',
      'required' => true,
      'label' => '新品标识',
    ),
    'sync_status' =>
    array (
     'type' =>
      array (
        0 => '未同步',
        1 => '同步失败',
        2 => '同步中',
        3 => '同步成功',
        4 => '同步后编辑',
      ),
      'default' => '0',
      'required' => true,
      'label' => '同步状态',
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
  ),
  'index' =>
      array (
        'ind_product_wms_out' =>
        array (
            'columns' =>
            array (
              0 => 'inner_sku',
              1 => 'wms_id',
            ),
            'prefix' => 'unique',
        ),
        'ind_inner_product_id' =>
        array (
            'columns' =>
            array (
              0 => 'inner_product_id',
            ),
        ),
        'ind_wms_id' =>
        array (
            'columns' =>
            array (
              0 => 'wms_id',
            ),
        ),
        'ind_sync_status' =>
        array (
            'columns' =>
            array (
              0 => 'sync_status',
            ),
        ),
      ),
  'comment' => '外部sku关联表',
  'engine' => 'innodb',
  'version' => '$Rev: 40654 $',
);