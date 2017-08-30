<?php
/* 店铺商品表 */
$db['shop_items'] = array(
    'engine'  => 'innodb',
    'version' => '$Rev: $',
    'comment' => app::get('inventorydepth')->_('店铺商品表'),
    'columns' => array(
        'id' => array(
            'type'     => 'varchar(32)',
            'required' => true,
            'pkey'     => true,
            //'extra'    => 'auto_increment',
            'label'    => 'ID',
        ),
        'shop_id' => array(
            'type'     => 'table:shop@ome',
            'required' => true,
            'comment'  => app::get('inventorydepth')->_('店铺ID'),
            'filterdefault'   => true,
            'filtertype'      => true,
            'label'           => app::get('inventorydepth')->_('前端店铺'),
        ),
        'shop_bn' => array(
            'type'            => 'bn',
            'label'           => app::get('inventorydepth')->_('店铺编码'),
            'required'        => true,
            'in_list'         => true,
            'filterdefault'   => true,
            'filtertype'      => true,
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
            //'filterdefault'   => true,
            //'filtertype'      => true,
            'order'           => 20,  
        ),
        'shop_type' => array(
            'type'     => 'varchar(30)',
            'label'    => app::get('inventorydepth')->_('店铺类型'),
            'required' => true,
            'default'  => '',
            'hidden'   => true,
        ),
        'iid' => array(
            'type'     => 'varchar(50)',
            'label'    => app::get('inventorydepth')->_('店铺商品数字ID'),
            'required' => false,
            'comment'  => app::get('inventorydepth')->_('店铺商品数字ID'),
        ),
        'bn' => array(
            'type'            => 'bn',
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('店铺商品编码'),
            'in_list'         => true,
            'default_in_list' => true,
            'filtertype'      => true,
            'order'           => 40,
            'searchtype' => 'nequal',
        ),
        'title' => array(
            'type'            => 'varchar(80)',
            'required'        => true,
            'label'           => app::get('inventorydepth')->_('店铺商品名称'),
            'searchtype'      => 'has',
            'in_list'         => true,
            'default_in_list' => true,
            'filterdefault'   => true,
            'filtertype'      => true,
            'additional'      => true,
            'order' => 50,
        ),
        'detail_url' => array(
            'type'            => 'text',
            'default'         => '',
            'label'           => app::get('inventorydepth')->_('访问URL'),
            'in_list'         => true,
        ),
        'approve_status' => array(
            'type'            => array(
                'onsale' => app::get('inventorydepth')->_('上架'),
                'instock' => app::get('inventorydepth')->_('下架'),
                //'sale_on_time'=>app::get('inventorydepth')->_('定时上架'),
            ),
            'default'         => 'onsale',
            'label'           => app::get('inventorydepth')->_('商品在架状态'),
            'panel_id' => 'frame_finder_top',
            'filterdefault' => true,
            //'in_list'         => true,
            //'default_in_list' => true,
            'filtertype'      => true,
            'order' => 10
        ),
        'default_img_url' => array(
            'type' => 'text',
            'default' => '',
            'comment' => app::get('inventorydepth')->_('图片地址'),
        ),
        'disabled' => array(
            'type'    => 'bool',
            'default' => 'false',      
        ),
        'frame_set' => array(
            'type' => 'bool',
            'label' => app::get('inventorydepth')->_('上下架'),
            'default' => 'true',
        ),
        'download_time' => array(
            'type' => 'time',
            'label' => app::get('inventorydepth')->_('同步时间'),
            'default' => 0,
            'in_list' => true,
            'default_in_list' => true,
            'order' => 70
        ),
        'price' => array(
            'type' => 'money',
            'label' => app::get('inventorydepth')->_('销售价'),
            'default' => 0,
            'in_list' => true,
        ),
        'sku_num' => array(
            'type' => 'mediumint(8)',
            'label' => app::get('inventorydepth')->_('SKU数'),
            'default' => 0,
        ),
        'store_info' => array(
            'type' => 'varchar(30)',
            'label' => app::get('inventorydepth')->_('前端可售预估数/总可售预估数'),
            'default' => '',
            //'in_list' => true,
            //'width' => 200,
            'hidden' => true,
        ),
        'statistical_time' => array(
            'type' => 'time',
            'label' => app::get('inventorydepth')->_('库存信息统计时间'),
            'default' => 0,
            'hidden' => true,
        ),
        'shop_store' => array(
            'type' => 'number',
            'label' => app::get('inventorydepth')->_('店铺库存'),
            'default' => 0,
            'filtertype' => 'normal',
            'panel_id' => 'frame_finder_top',
            'filterdefault' => true,
            'default_in_list' => true,
            'in_list' => true,
        ),
        'taog_store' => array(
            'type' => 'number',
            'label' => app::get('inventorydepth')->_('可售库存'),
            'default' => 0,
            'filtertype' => 'normal',
            'panel_id' => 'frame_finder_top',
            'filterdefault' => true,
            'default_in_list' => true,
            'in_list' => true,
        ),
    ),
    'index'   => array(
        /*
        'ind_iid_shopid' => array(
            'columns' => array('shop_id','iid'),
            'prefix' => 'UNIQUE',
        ),*/
        'idx_iid' => array(
            'columns' => array('iid'),
        ),
        'idx_shop_id' => array(
            'columns' => array('shop_id'),
        ),
        'ind_bn' => array(
            'columns' => array('bn'),
        ),
        'ind_shop_bn_crc32' => array(
            'columns' => array('shop_bn_crc32'),
        ),
    ),
);
