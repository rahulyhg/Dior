<?php
$db['dly_corp_items']=array (
  'columns' =>
  array (

    'corp_id' =>
    array (
      'type' => 'table:dly_corp@ome',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'pkey' => true,
    ),
    'region_id' =>
    array (
      'type' => 'table:regions@eccommon',
      'required' => true,
      'default' => '0',
      'editable' => false,
      'pkey' => true,
    ),

   'areagroupbakid' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => '0',
      'editable' => false,

    ),

    'firstunit' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'required' => true,
    ),
    'continueunit' =>
    array (
      'type' => 'number',
      'editable' => false,
      'default' => 0,
      'required' => true,
    ),
    'firstprice' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'continueprice' =>
    array (
      'type' => 'money',
      'editable' => false,
    ),
    'dt_expressions' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'dt_useexp' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
    ),
    ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);