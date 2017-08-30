<?php
$db['relate']=array (
    'columns' => array (
        'relate_id' => array (
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'relate_table' => array (
            'type' => 'varchar(50)',
            'editable' => false,
            'label' => app::get('omeanalysts')->_('关联表'),
        ),
        'relate_key' => array (
            'type' => 'varchar(50)',
            'editable' => false,
            'label' => app::get('omeanalysts')->_('关联表ID'),
        ),
        'disabled' => array (
            'type' => 'bool',
            'required' => true,
            'editable' => false,
            'default' => 'false',
        ),
    ),
    'index' =>
      array (
        'ind_relate_table' =>
        array (
            'columns' =>
            array (
              0 => 'relate_table',
            ),
        ),
        'ind_relate_key' =>
        array (
            'columns' =>
            array (
              0 => 'relate_key',
            ),
            'prefix' => 'unique',
        ),
      ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);