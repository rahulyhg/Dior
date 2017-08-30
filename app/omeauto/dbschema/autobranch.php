<?php
$db['autobranch']=array (
  'columns' => 
  array (
    'tid' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      ),
    'bid' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
    ),
   'weight'=>array(
   'type' => 'tinyint',
   'default'=>0,

   ),
   'is_default' =>
    array (
      'type' => 'intbool',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
  ),
  
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);