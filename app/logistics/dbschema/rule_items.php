<?php
$db['rule_items']=array (
    'columns' =>
    array (
        'item_id' =>
        array (
          'type' => 'int unsigned',
          'required' => true,
          'pkey' => true,
          'editable' => false,
          'extra' => 'auto_increment',
        ),
        'obj_id' =>
        array (
        'type' => 'table:rule_obj@logistics',
        'required' => true,
        'default' => 0,
        'editable' => false,
        ),
        'min_weight' =>
        array (
            'type' => 'number',
            'editable' => false,
        ),
        'max_weight' =>
        array(
            'type' => 'int',
            'editable' => false,
        ),
        'corp_id'=>array (
            'type' => 'int',
            'editable' => false,
        ),
        'second_corp_id'=>array (
            'type' => 'int',
            'editable' => false,
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);