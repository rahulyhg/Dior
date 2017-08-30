<?php
/**
 * @copyright shopex.cn
 */

$db['regulation_apply'] = array(
    'comment' => '规则应用中心',
    'columns' => array(
        'id' => array(
            'type'     => 'mediumint(8) unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'comment'  => ''
        ),
        'bn' => array(
            'type'            => 'bn',
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('应用编码'),
            'in_list'         => true,
            'default_in_list' => true,
            'comment'         => ''
        ),
        'heading' => array(
            'type'            => 'varchar(200)',
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('应用名称'),
            'is_title'        => true,
            'in_list'         => true,
            'default_in_list' => true,
            'searchtype'      => 'has',
            'comment'         => '',
        ),
        'condition' => array(
            'type' => array(
                'frame' => '商品上下架', 'stock' => '库存更新'
            ),
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('规则类型'),
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'comment'         => ''
        ),
        'style' => array(
            'type' => array(
                'fix' => '定时', 'stock_change' => '库存变动'
            ),
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('触发条件'),
            'in_list'         => true,
            'default_in_list' => true,
            'searchtype'      => 'nequal',
            'comment'         => ''
        ),

        'start_time' => array(
            'type'            => 'time',
            'required'        => false,
            'label'           => app::get('inventorydepth')->_('开启时间'),
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'comment'         => ''
        ),
        'end_time' => array(
            'type'            => 'time',
            'required'        => false,
            'label'           => app::get('inventorydepth')->_('截止时间'),
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'comment'         => ''
        ),
        'shop_id' => array(
            'type'     => 'longtext',
            'required' => true,
            'label'    => app::get('inventorydepth')->_('店铺名称'),
            'comment'  => '对应店铺表的shop_id'
        ),
        'using' => array(
            'type'            => 'bool',
            'required'        => false,
            'default'         => 'false',
            'label'           => app::get('inventorydepth')->_('启用状态'),
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'comment'         => ''
        ),
        'al_exec' => array(
            'type'     => 'bool',
            'required' => true,
            'default'  => 'false',
            'label'    => app::get('inventorydepth')->_('是否执行'),
            'comment'  => ''
        ),
        'exec_time' => array(
            'type'     => 'time',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('最后执行时间'),
            'comment'  => ''
        ),
        'operator' => array(
            'type'     => 'table:account@pam',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('操作人'),
            'in_list'  => true,
            'comment'  => ''
        ),
        'update_time' => array(
            'type'     => 'last_modify',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('最后更新时间'),
            'in_list'  => true,
            'comment'  => '',
        ),
        'operator_ip' => array(
            'type'     => 'ipaddr',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('操作人IP'),
            'comment'  => ''
        ),
        'apply_goods' => array(
            'type'     => 'longtext',
            'default' => null,
        ),
        'apply_pkg' => array(
            'type' => 'longtext',
            'default' => null,
        ),
        'regulation_id' => array(
            'type'     => 'table:regulation@inventorydepth',
            'required' => true,
            'label'    => app::get('inventorydepth')->_('规则Id'),
            'comment'  => ''
        ),
        'priority' => array(
            'type'            => 'mediumint(8)',
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('规则应用等级'),
            'default'         => 1,
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'type' => array(
            'type' => array(
                0 => app::get('inventorydepth')->_('全局级'),
                1 => app::get('inventorydepth')->_('店铺级'),
                2 => app::get('商品级')
            ),
            'default' => '2',
        ),
    ),
    'index' => array(
        'idx_start_time' => array(
            'columns' => array('start_time')
        ),
        'idx_end_time' => array(
            'columns' => array('end_time')
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $'
);
