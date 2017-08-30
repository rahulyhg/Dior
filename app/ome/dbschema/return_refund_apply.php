<?php
$db['return_refund_apply']=array (
  'columns' => 
  array(
    'refund_apply_id' => 
    array (
      'type' => 'table:refund_apply@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    'return_id' => 
    array(
      'type' => 'table:return_product@ome',
      'required' => true,
      'pkey' => true,
      'editable' => false,
    ),
    
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);