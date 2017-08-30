<?php
/**
 * @copyright shopex.cn
 * @author chenping<chenping@shopex.cn>
 */

$db['log'] = array(
    'comment' => '库存深度日志',
    'columns' => array(
        'log_id' => array(
            'type'     => 'mediumint(8) unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => 'ID',
            'comment'  => ''
        ),
        'bn' => array(
            'type' => 'varchar(30)',
            'label' => app::get('inventorydepth')->_('货号'),
            'required' => true,
            'default' => '',
        ),
        'sku_id' => array(
            'type' => 'varchar(30)',
            'label' => app::get('inventorydepth')->_('货品SKU的ID'),
            'default' => '',
        ),
        'shop_id' => array(
            'type' => 'varchar(32)',
            'label' => app::get('inventorydepth')->_('店铺ID'),
            'required' => true,
            'default' => '',
        ),
        'shop_bn' => array(
            'type' => 'varchar(20)',
            'label' => app::get('inventorydepth')->_('店铺编号'),
            'required' => true,
            'default' => '',
        ),
        'type' => array(
            'type' => array(
                'stock' => app::get('inventorydepth')->_('库存回写'),
                'frame' => app::get('inventorydepth')->_('上下架'),
            ),
            'label' => app::get('inventorydepth')->_('日志类型'),
        ),
        'status' => array(
            'type' => array(
                'success' => app::get('inventorydepth')->_('成功'),
                'fail' => app::get('inventorydepth')->_('失败'),
                'running' => app::get('inventorydepth')->_('运行中'),
            ),
            'label' => app::get('inventorydepth')->_('状态'),
        ),
        'last_modified' => array(
            'type' => 'last_modify',
            'label' => app::get('inventorydepth')->_('最后更新时间'),
            'default' => 0,
        ),
        'params' => array(
            'type' => 'longtext',
            'label' => app::get('inventorydepth')->_('参数值'),
            'default' => '',
        ),
        'msg' => array(
            'type' => 'longtext',
            'label' => app::get('inventorydepth')->_('输出信息'),
            'default' => '',
        ),
    ),
    'index' => array(
        'idx_sku_shop' => array(
            'columns' => array(
                0 => 'sku_id',
                1 => 'shop_id',
            ),
        ),
        'ind_bn_shop' => array(
            'columns' => array(
                0 => 'bn',
                1 => 'shop_id',
            ),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $',
);
