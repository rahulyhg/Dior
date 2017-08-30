<?php
$db['token']=array (
  'columns' => 
  array (
     'name' => 
    array (
      'type' => 'varchar(10)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'access_token' =>
    array (
      'type' => 'varchar(200)',
      'required' => false,
      'default' => '',
    ),
	'expires_in' =>
    array (
      'type' => 'int(10)',
      'required' => false,
    ),
	'createtime' =>
    array (
      'type' => 'time',
      'required' => false,
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);