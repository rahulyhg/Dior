<?php
$db['stock_account']=array (
  'columns' => 
  array (
    'account_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'account_bn' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'width' => 100,
      'label' => '商品货号',
    ),
	'account_ym' => 
    array (
      'type' => 'varchar(12)',
      'label' => '对账年月',
    ),
	'account_time' => 
    array (
      'type' => 'time',
      'label' => '创建时间',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'account_type' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'label' => '类型',
    ),
    'wms_id' =>
    array (
      'label' => 'WMS',
      'type' => 'varchar(32)',
      'in_list' => true,
      'default_in_list' => true,
    ),
	'd1' =>
    array (
      'label' => '1日',
      'type' => 'varchar(60)'
    ),
	'd2' =>
    array (
      'label' => '2日',
      'type' => 'varchar(60)'
    ),
	'd3' =>
    array (
      'label' => '3日',
      'type' => 'varchar(60)'
    ),
	'd4' =>
    array (
      'label' => '4日',
      'type' => 'varchar(60)'
    ),
	'd5' =>
    array (
      'label' => '5日',
      'type' => 'varchar(60)'
    ),
	'd6' =>
    array (
      'label' => '6日',
      'type' => 'varchar(60)'
    ),
	'd7' =>
    array (
      'label' => '7日',
      'type' => 'varchar(60)'
    ),
	'd8' =>
    array (
      'label' => '8日',
      'type' => 'varchar(60)'
    ),
	'd9' =>
    array (
      'label' => '9日',
      'type' => 'varchar(60)'
    ),
	'd10' =>
    array (
      'label' => '10日',
      'type' => 'varchar(60)'
    ),
	'd11' =>
    array (
      'label' => '11日',
      'type' => 'varchar(60)'
    ),
	'd12' =>
    array (
      'label' => '12日',
      'type' => 'varchar(60)'
    ),
	'd13' =>
    array (
      'label' => '13日',
      'type' => 'varchar(60)'
    ),
	'd14' =>
    array (
      'label' => '14日',
      'type' => 'varchar(60)'
    ),
	'd15' =>
    array (
      'label' => '15日',
      'type' => 'varchar(60)'
    ),
	'd16' =>
    array (
      'label' => '16日',
      'type' => 'varchar(60)'
    ),
	'd17' =>
    array (
      'label' => '17日',
      'type' => 'varchar(60)'
    ),
	'd18' =>
    array (
      'label' => '18日',
      'type' => 'varchar(60)'
    ),
	'd19' =>
    array (
      'label' => '19日',
      'type' => 'varchar(60)'
    ),
	'd20' =>
    array (
      'label' => '20日',
      'type' => 'varchar(60)'
    ),
	'd21' =>
    array (
      'label' => '21日',
      'type' => 'varchar(60)'
    ),
	'd22' =>
    array (
      'label' => '22日',
      'type' => 'varchar(60)'
    ),
	'd23' =>
    array (
      'label' => '23日',
      'type' => 'varchar(60)'
    ),
	'd24' =>
    array (
      'label' => '24日',
      'type' => 'varchar(60)'
    ),
	'd25' =>
    array (
      'label' => '25日',
      'type' => 'varchar(60)'
    ),
	'd26' =>
    array (
      'label' => '26日',
      'type' => 'varchar(60)'
    ),
	'd27' =>
    array (
      'label' => '27日',
      'type' => 'varchar(60)'
    ),
	'd28' =>
    array (
      'label' => '28日',
      'type' => 'varchar(60)'
    ),
	'd29' =>
    array (
      'label' => '29日',
      'type' => 'varchar(60)'
    ),
	'd30' =>
    array (
      'label' => '30日',
      'type' => 'varchar(60)'
    ),
	'd31' =>
    array (
      'label' => '31日',
      'type' => 'varchar(60)'
    ),
    
  ),
  'index' =>
  array (
    'ind_account_bn' =>
    array (
        'columns' =>
        array (
          0 => 'account_bn',
        ),
    ),
    'ind_account_type' =>
    array (
        'columns' =>
        array (
          0 => 'account_type',
        ),
    ),
	'ind_account_ym' =>
    array (
        'columns' =>
        array (
          0 => 'account_ym',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);
