<?php
$db['order_sync_status'] = array(
    'comment' => '订单同步状态表',
    'columns' => array(
        'order_id' => array(
            'type' => 'table:orders@ome',
            'pkey' => true,
        ),
        'type' => array(
            'type' => 'smallint',
            'default' => '0',
            'label' => '同步类型'
        ),
        'sync_status' => array(
            'type' => 'smallint',
            'default' => '0',
            'comment' => '0:未同步,1:同步失败,2:同步成功',
            'label' => '同步状态'
        ),
    ),
    'engine' => 'innodb',
    'version' => '$Rev: $'
);
