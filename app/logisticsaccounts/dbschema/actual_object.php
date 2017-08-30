<?php
$db['actual_object']=array (
  'columns' =>
  array (
    'obj_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'eid'=>array(
      'type' => 'table:estimate@logisticsaccounts',
      'required' => true,
      'default' => 0,
    ),
    'aid'=>array(
    'type' =>'table:actual@logisticsaccounts',
    'required'=>true,
    'default'=>0,
    ),
    'status'=>array(
    'type'=>array(
      0=>'否',
    1=>'是',
    ),
    'default'=>'0',
    'label'=>'是否异常'
  ),
    'memo'=>array(
    'type'=>'text',
    'label'=>'备注'
   ),
),
'index' =>
  array (
    'uni_indx' =>
    array (
      'columns' =>
      array (
       0 => 'eid',
       1 => 'aid',
      ),
      'prefix' => 'UNIQUE',
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);