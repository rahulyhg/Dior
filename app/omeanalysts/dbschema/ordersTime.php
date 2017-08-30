<?php
$db['ordersTime']=array (
  'columns' =>
  array (
    'time_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'dates' =>
    array (
      'type' => 'int',
      'label' => '日期',
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
    ),
    'h1' =>
    array (
      'type' => 'int unsigned',
      'label' => '1点',
      'in_list' => true,
    ),
    'h2' =>
    array (
      'type' => 'int unsigned',
      'label' => '2点',
      'in_list' => true,
    ),
    'h3' =>
    array (
      'type' => 'int unsigned',
      'label' => '3点',
      'in_list' => true,
    ),
    'h4' =>
    array (
      'type' => 'int unsigned',
      'label' => '4点',
      'in_list' => true,
    ),
    'h5' =>
    array (
      'type' => 'int unsigned',
      'label' => '5点',
      'in_list' => true,
    ),
    'h6' =>
    array (
      'type' => 'int unsigned',
      'label' => '6点',
      'in_list' => true,
    ),
    'h7' =>
    array (
      'type' => 'int unsigned',
      'label' => '7点',
      'in_list' => true,
    ),
    'h8' =>
    array (
      'type' => 'int unsigned',
      'label' => '8点',
      'in_list' => true,
    ),
    'h9' =>
    array (
      'type' => 'int unsigned',
      'label' => '9点',
      'in_list' => true,
    ),
    'h10' =>
    array (
      'type' => 'int unsigned',
      'label' => '10点',
      'in_list' => true,
    ),
    'h11' =>
    array (
      'type' => 'int unsigned',
      'label' => '11点',
      'in_list' => true,
    ),
    'h12' =>
    array (
      'type' => 'int unsigned',
      'label' => '12点',
      'in_list' => true,
    ),
    'h13' =>
    array (
      'type' => 'int unsigned',
      'label' => '13点',
      'in_list' => true,
    ),
    'h14' =>
    array (
      'type' => 'int unsigned',
      'label' => '14点',
      'in_list' => true,
    ),
    'h15' =>
    array (
      'type' => 'int unsigned',
      'label' => '15点',
      'in_list' => true,
    ),
    'h16' =>
    array (
      'type' => 'int unsigned',
      'label' => '16点',
      'in_list' => true,
    ),
    'h17' =>
    array (
      'type' => 'int unsigned',
      'label' => '17点',
      'in_list' => true,
    ),
    'h18' =>
    array (
      'type' => 'int unsigned',
      'label' => '18点',
      'in_list' => true,
    ),
    'h19' =>
    array (
      'type' => 'int unsigned',
      'label' => '19点',
      'in_list' => true,
    ),
    'h20' =>
    array (
      'type' => 'int unsigned',
      'label' => '20点',
      'in_list' => true,
    ),
    'h21' =>
    array (
      'type' => 'int unsigned',
      'label' => '21点',
      'in_list' => true,
    ),
    'h22' =>
    array (
      'type' => 'int unsigned',
      'label' => '22点',
      'in_list' => true,
    ),
    'h23' =>
    array (
      'type' => 'int unsigned',
      'label' => '23点',
      'in_list' => true,
    ),
    'h24' =>
    array (
      'type' => 'int unsigned',
      'label' => '24点',
      'in_list' => true,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);