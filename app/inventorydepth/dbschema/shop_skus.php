<?php
/**
 * @copyright shopex.cn
 * @author ymz lymz.86@gmail.com
 * @version ocs
 */

$db['shop_skus'] = array(
    'comment' => '',
    'columns' => array(
        'id' => array(
            'type' => 'varchar(32)',
            'required' => true,
            'pkey' => true,
            //'extra' => 'auto_increment',
            'label' => 'ID',
            'comment' => ''
        ),
        'request' => array(
            'type' => 'bool',
            'label' => app::get('inventorydepth')->_('回写库存'),
            'default' => 'true',
            //'in_list' => true,
            //'default_in_list' => true,
            //'order' => 5,
        ),
        'shop_id' => array(
            'type' => 'varchar(32)',
            'required' => true,
        ),
        'shop_bn' => array(
            'type'            => 'bn',
            'label'           => app::get('inventorydepth')->_('店铺编码'),
            'required'        => true,
            'filterdefault'   => true,
            'filtertype'      => true,
            'order'           => 10,
        ),
        'shop_bn_crc32' => array(
            'type'     => 'bigint(20)',
            'required' => true,
            'default'  => 0,
        ),      
        'shop_name' => array(
            'type'            => 'varchar(255)',
            'label'           => app::get('inventorydepth')->_('店铺名称'),
            'required'        => true,
            //'in_list'         => true,
            //'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => true,
            'order'           => 20,  
        ),
        'shop_type' => array(
            'type'     => 'varchar(30)',
            'label'    => app::get('inventorydepth')->_('店铺类型'),
            'required' => true,
            'default'  => '',
            'hidden'   => true,
        ),
        'shop_sku_id'   => array(
            'type'            => 'varchar(50)',
            'required'        => false,
            'label'           => app::get('inventorydepth')->_('店铺货品ID'),
            'order'           => 30
        ),
        'shop_iid' => array(
            'type'            => 'varchar(50)',
            'required'        => false,
            'label'           => app::get('inventorydepth')->_('店铺商品ID'),
            'order'           => 40,
        ),
        'simple'         => array(
            'type' => 'bool',
            'label' => app::get('inventorydepth')->_('简单商品'),
            'default' => 'false',
        ),
        'shop_product_bn' => array(
            'type'            => 'varchar(200)',
            'required'        => false,
            'label'           => app::get('inventorydepth')->_('店铺货号'),
            'default'         => '',
            'in_list' => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => 'normal',
            'order'           => 60,
            'searchtype' => 'has', 
        ),
        'shop_product_bn_crc32' => array(
            'type' => 'bigint(20)',
            'required' => true,
            'default' => 0,
        ),
        'shop_properties' => array(
            'type' => ' longtext',
            'label' => app::get('inventorydepth')->_('店铺货品属性值'),
            'default' => '',
            //'in_list' => true,
            //'default_in_list' => true,
        ),
        'shop_properties_name' => array(
            'type' => 'longtext',
            'label' => app::get('inventorydepth')->_('店铺货品属性'),
            'default' => '',
            'in_list' => true,
        ),
        'shop_title' => array(
            'type' => 'varchar(80)',
            'label' => app::get('inventorydepth')->_('店铺货品名称'),
            'default' => '',
            'filtertype' => 'normal',
            'in_list' => true,
            'default_in_list' => true,
            'order' => 70,
            'searchtype' => 'nequal'
        ),
        'shop_price' => array(
            'type'            => 'money',
            'label'           => app::get('inventorydepth')->_('销售价'),
            'default'         => 0,
            'in_list'         => true,
            //'default_in_list' => true,
        ),
        'shop_barcode' => array(
            'type' => 'varchar(30)',
            'label' => app::get('inventorydepth')->_('条形码'),
            'default' => '',
            'filtertype' => 'normal',
        ),
        'release_stock' => array(
            'type'            => 'int(10) unsigned',
            'required'        => false,
            'default'         => 0,
            'label'           => app::get('inventorydepth')->_('发布库存'),
            'in_list'         => false,
            'default_in_list' => false,
            'comment'         => '',
            'hidden'          => true,
            'order' => 100
        ),
        'shop_stock' => array(
            'type'            => 'int(10) unsigned',
            'required'        => false,
            'default'         => 0,
            'label'           => app::get('inventorydepth')->_('店铺库存'),
            'in_list'         => false,
            'default_in_list' => false,
            'comment'         => '',
            'order' => 80,
        ),
        'operator' => array(
            'type'     => 'varchar(100)',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('发布操作人'),
            'comment'  => ''
        ),
        'operator_ip' => array(
            'type'     => 'ipaddr',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('发布操作人IP'),
            'comment'  => ''
        ),
        'update_time' => array(
            'type'     => 'last_modify',
            'required' => false,
            'label'    => app::get('inventorydepth')->_('最后更新时间'),
            'comment'  => '',
            'in_list' => true,
            //'default_in_list' => true,
        ),
        'download_time' => array(
            'type' => 'time',
            'label' => app::get('inventorydepth')->_('同步时间'),
            'in_list' => true,
            //'default_in_list' => true,
            'default' => 0,
        ),
        'mapping' => array(
            'type' => 'intbool',
            'label' => app::get('inventorydepth')->_('已对映上本地货品'),
            'default' => '0',
            'in_list' => true,
            'default_in_list' => true,
        ),
        'bind' => array(
            'type' => 'intbool',
            'default' => '0',
        ),
        'addon' => array(
            'type' => 'serialize',
            'default' => '',
        ),
        'release_status' => array(
            'type' => array (
                'running' => '运行中',
                'success' => '成功',
                'fail'    => '失败',
                'sending' => '发起中',
                'sleep'   => '未发布',
            ),
            'label'           => app::get('inventorydepth')->_('发布状态'),
            'in_list'         => true,
            //'default_in_list' => true,
            'default'         => 'sleep',
            'order' => 110
        ),
    ), 
    'index' => array(
        /*
        'idx_shop_goods' => array(
            'columns' => array('shop_id','shop_iid', 'shop_sku_id'),
            'prefix' => 'UNIQUE',
        ),*/
        'idx_shop_bn_crc32' => array(
            'columns' => array('shop_bn_crc32'),
        ),
        'idx_shop_pbn_crc32' => array(
            'columns' => array('shop_product_bn_crc32'),
        ),
        'idx_shop_iid' => array(
            'columns' => array('shop_iid'),
        ),
        'idx_shop_id' => array(
            'columns' => array('shop_id'),
        ),
        'idx_shop_sku_id' => array(
            'columns' => array('shop_sku_id'),
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $'
);
