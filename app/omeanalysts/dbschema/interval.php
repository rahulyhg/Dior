<?php
$db['interval']=array (
    'columns' => array (
        'interval_id' => array (
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'from' => array (
            'type' => 'int unsigned',
            'label' => '起始价格',
            'in_list' => true,
        ),
        'to' => array (
            'type' => 'int unsigned',
            'label' => '截止价格',
            'in_list' => true,
        ),
      ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);