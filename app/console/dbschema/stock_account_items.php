<?php
$db['stock_account_items']=array (
  'columns' => 
                  array (
                    'items_id' => 
                    array (
                      'type' => 'number',
                      'required' => true,
                      'pkey' => true,
                      'extra' => 'auto_increment',
                      'editable' => false,
                    ),
                    'batch' => 
                    array (
                      'type' => 'varchar(64)',
                      'required' => false,
                      'editable' => false,
                      'searchtype' => 'has',
                      'filtertype' => 'normal',
                      'filterdefault' => true,
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 150,
                      'label' => '批次号',
                    ),
                    'account_bn' => 
                    array (
                      'type' => 'varchar(32)',
                      'required' => true,
                      'editable' => false,
                      'searchtype' => 'has',
                      'filtertype' => 'normal',
                      'filterdefault' => true,
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 150,
                      'label' => '商品货号',
                    ),
                    'account_time' => 
                    array (
                      'type' => 'time',
                      'label' => '日期',
                      'width' => 80,
                      'editable' => false,
                      'in_list' => true,
                      'default_in_list' => true,
                    ),
                    'original_goods_stock' =>
                    array (
                      'label' => '仓库良品数量',
                      'type' => 'mediumint',
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 120,
                      'default' => 0,
                    ),
                    'account_goods_stock' =>
                    array (
                      'label' => '良品数量',
                      'type' => 'mediumint',
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 120,
                      'default' => 0,
                    ),
                    'goods_diff_nums' =>
                    array (
                      'label' => '良品差异',
                      'type' => 'mediumint',
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 100,
                      'default' => 0,
                    ),
                    'original_rejects_stock' =>
                    array (
                      'label' => '仓库不良品数量',
                      'type' => 'mediumint',
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 120,
                      'default' => 0,
                    ),
                    'account_rejects_stock' =>
                    array (
                      'label' => '不良品数量',
                      'type' => 'mediumint',
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 120,
                      'default' => 0,
                    ),
                    'rejects_diff_nums' =>
                    array (
                      'label' => '不良品差异',
                      'type' => 'mediumint',
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 100,
                      'default' => 0,
                    ),
                    'wms_id' =>
                    array (
                      'label' => 'WMS',
                      'type' => 'varchar(32)',
                      'in_list' => true,
                      'default_in_list' => true,
                      'width' => 120
                    ),
                ),
  'index' =>
  array (
    'ind_bacth_bn' =>
    array (
        'columns' => array (
            'batch','account_bn'
         ),
    ),
	'ind_account_time' =>
    array (
        'columns' =>
        array (
          0 => 'account_time',
        ),
    ),
),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);

