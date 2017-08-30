<?php
$db['operation_log'] = array(
    'columns' => array(
        'log_id' => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
        ),
        'obj_type' => array(
            'type'     => 'varchar(30)',
            'required' => true,
        ),
        'obj_id' => array(
            'type'     => 'varchar(32)',
            'required' => true,
        ),
        'memo' => array(
            'type'     => 'longtext',
            'required' => true,
        ),
        'create_time' => array(
            'type'     => 'time',
            'required' => true,
        ),
        'op_id' => array(
            'type'     => 'mediumint unsigned',
            'required' => true,
        ),
        'op_name' => array(
            'type'     => 'varchar(100)',
            'required' => true,
        ),
        'operation' => array(
            'type' => 'varchar(50)',
            'required' => true,
        ),
    ),
    'index' => array(
        'idx_obj' => array('columns' => array('obj_id','obj_type')),
    ),
); 