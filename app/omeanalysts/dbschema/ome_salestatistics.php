<?php
$db['ome_salestatistics']=array (
    'columns' => array (
        'record_id' => array (
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
            'editable' => false,
        ),
        'shop_id' => array (
            'type' => 'varchar(40)',
            'editable' => false,
            'label' => app::get('omeanalysts')->_('店铺'),
        ),
        'day' => array (
            'type' => 'time',
            'editable' => false,
            'label' => app::get('omeanalysts')->_('统计日期'),
        ),
        'order_num' => array (
            'type' => 'number',
            'editable' => false,
            'label' => app::get('omeanalysts')->_('下单量'),
        ),
        'delivery_num' => array (
            'type' => 'number',
            'required' => true,
        	'label' => app::get('omeanalysts')->_('发货量'),
            'editable' => false,
        ),
        'sale_total' => array (
            'type' => 'money',
            'required' => true,
        	'label' => app::get('omeanalysts')->_('销售额'),
            'editable' => false,

        ),
        'minus_sale_total' => array (
            'type' => 'money',
            'required' => true,
        	'label' => app::get('omeanalysts')->_('负销售额'),
            'editable' => false,

        ),
        
        'return_total' => array (
            'type' => 'number',
            'required' => true,
        	'label' => app::get('omeanalysts')->_('售后量'),
            'editable' => false,
        ),
        'ok_return_total' => array (
            'type' => 'number',
            'required' => true,
        	'label' => app::get('omeanalysts')->_('完成售后量'),
            'editable' => false,
        ),
        
        'runtime' => array (
            'type' => 'time',
            'required' => true,
        	'label' => app::get('omeanalysts')->_('添加时间'),
            'editable' => false,
        ),
        
    ),
    'index' =>
      array (
        'ind_day' =>
        array (
            'columns' =>
            array (
              0 => 'day',
            ),
        ),
      ),
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);