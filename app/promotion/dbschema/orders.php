<?php
$db['orders']=array(
'columns' =>
    array (
        'rule_id' =>
        array (
            'type' => 'int(8)',
            'required' => true,
            'pkey' => true,
            'label' => app::get('b2c')->_('规则id'),
            'editable' => false,
            'extra' => 'auto_increment',
            'in_list' => false, 
            ),
        'name' =>
        array (
            'type' => 'varchar(255)',
            'required' => true,
            'default' => '',
            'label' => app::get('b2c')->_('规则名称'),
            'editable' => true,
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault'=>true,
            'is_title' => true,
            ),
        'description' =>
        array (
            'type' => 'text',
            'label' => app::get('b2c')->_('规则描述'),
            'required' => false,
            'default' => '',
            'editable' => false,
            'in_list' => true,
            'filterdefault'=>true,
            ),
        'from_time' =>
        array (
            'type' => 'time',
            'label' => app::get('b2c')->_('起始时间'),
            'default'=> 0,
            'editable' => true,
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault'=>true,
            ),
        'to_time' =>
        array (
            'type' => 'time',
            'label' => app::get('b2c')->_('截止时间'),
            'default'=> 0,
            'editable' => true,
            'in_list' => true,
            'default_in_list' =>true,
            'filterdefault'=>true,
            ),
        'status' =>
        array (
            'type' => 'bool',
            'default' => 'false',
            'required' => true,
            'label' => app::get('b2c')->_('开启状态'),
            'in_list' => true,
            'editable' => false,
            'filterdefault'=>true,
            'default_in_list' => true,
            ),
        'shop' =>
            array (
            'type' => 'varchar(255)',
            'required' => true,
            'default' => '',
            'label' => app::get('b2c')->_('适用店铺'),
        ),
        'source' =>
            array (
            'type' => 'varchar(255)',
            'required' => true,
            'default' => '',
            'label' => app::get('b2c')->_('订单来源'),
        ),
        'conditions_serialize' =>
        array (
            'type' => 'serialize',
            'default' => '',
            'required' => true,
            'label' => app::get('b2c')->_('规则条件'),
            'editable' => false,
            ),
        'actions_serialize' =>
        array (
            'type' => 'serialize',
            'default' => '',
            'label' => app::get('b2c')->_('动作行为'),
            'editable' => false,
            ),
        'conditions' =>
        array (
            'type' => 'varchar(200)',
            'default' => '',
            'required' => true,
            'label' => app::get('b2c')->_('应用规则'),
            'editable' => false,
            ),
		'actions' =>
        array (
            'type' => 'varchar(200)',
            'default' => '',
            'required' => true,
            'label' => app::get('b2c')->_('行为'),
            'editable' => false,
            ),
        'free_shipping' =>
        array(
            'type' =>array(
                    0=>app::get('b2c')->_('免运费'),
                    1=>app::get('b2c')->_('满足过滤条件的商品免运费'),
                    2=>app::get('b2c')->_('全场免运费')
             ),
            'default' => '0',
            'label' => app::get('b2c')->_('免运费'),
            'editable' => false,
            'filterdefault'=>true,
            'in_list' => false,
            ),
	'shop_type' =>
        array(
            'type' =>array(
                    0=>app::get('b2c')->_('ecos.b2c'),
                    1=>app::get('b2c')->_('第三方商城'),
             ),
            'default' => '1',
            'label' => app::get('b2c')->_('店铺类型'),
          //  'editable' => false,
          //  'filterdefault'=>true,
          //  'in_list' => false,
            ),
       'rule_type' =>
            array (
            'type' => array (
                'N' => app::get('b2c')->_('普通规则'),
                'C' => app::get('b2c')->_('优惠券规则'),
            ),
            'default' => 'N',
            'required' => true,
            'editable' => false,
            ),
        ),
    'comment' => app::get('b2c')->_('订单促销规则'),
);