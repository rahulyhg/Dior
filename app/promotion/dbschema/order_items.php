<?php
$db['order_items']=array(
'columns' =>
    array (
        'id' =>
        array (
                'type' => 'int(8)',
                'required' => true,
                'pkey' => true,
                'label' => app::get('b2c')->_('自增id'),
                'editable' => false,
                'extra' => 'auto_increment',
                'in_list' => false, 
            ),
        'rule_id' =>
        array (
                'type' => 'int(8)',
                'required' => true,
                'label' => app::get('b2c')->_('规则id'),
            ),
        'type' =>
        array (
                'type' => 'varchar(8)',
                'required' => true,
                'default' => 'gift',
                'label' => app::get('b2c')->_('赠品类型'),
            ),
        'primary_key' =>
        array (
                'type' => 'int(8)',
                'required' => true,
                'label' => app::get('b2c')->_('primary_key'),
            ),
        'nums' =>
        array (
                'type' => 'int(8)',
                'required' => true,
                'default'=>0,
                'label' => app::get('b2c')->_('数量'),
            ),
        ),
        'index' => 
        array (
            'ind_rule_id' => 
                array (
                'columns' => 
                    array (
                        0 => 'rule_id',
                    ),
            ),
            'ind_type' => 
                array (
                'columns' => 
                    array (
                        0 => 'type',
                    ),
            ),
            'ind_primary_key' => 
                array (
                'columns' => 
                    array (
                        0 => 'primary_key',
                    ),
            ),
        ),
    'comment' => app::get('b2c')->_('赠品'),
);