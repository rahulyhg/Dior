<?php
$db['apiconfig']=array (
    'columns' =>
    array (
    'ax_id' =>
        array (
            'type' => 'number',
            'required' => true,
            'pkey' => true,
            'extra' => 'auto_increment',
        ),
    'shop_id'=>
        array(
            'type' => 'varchar(255)',
            'required' => true,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '店铺',
        ),
	 'secret_key'=>
        array(
            'type' => 'varchar(100)',
            //'required' => true,
            'in_list' => true,
            'default_in_list' => true,
            'label' => '接口参数secret_key',
        ),
    'warehouseCode'=>
    array(
        'type' => 'varchar(30)',
        //'required' => true,
        'in_list' => true,
        'default_in_list' => true,
        'label' => '仓库编码(奇门)',
    ),
    'ownerCode'=>
    array(
        'type' => 'varchar(30)',
        //'required' => true,
        'in_list' => true,
        'default_in_list' => true,
        'label' => '货主编码(奇门)',
    ),
    'shopNick'=>
    array(
        'type' => 'varchar(50)',
        //'required' => true,
        'in_list' => true,
        'default_in_list' => true,
        'label' => '店铺名称(奇门)',
    ),
    'sourcePlatformName'=>
    array(
        'type' => 'varchar(50)',
        //'required' => true,
        'in_list' => true,
        'default_in_list' => true,
        'label' => '订单来源平台名称(奇门)',
    ),
	'crm_api_shipurl'=>
        array(
            'type' => 'varchar(255)',
            'required' => true,
            'in_list' => true,
            'default_in_list' => true,
            'label' => 'CRM发货请求地址',
        ),
	'crm_api_receiveurl'=>
        array(
            'type' => 'varchar(255)',
            'required' => true,
            'in_list' => true,
            'default_in_list' => true,
            'label' => 'CRM签收请求地址',
        ),
    'ax_file_prefix' =>
        array (
            'type' => 'longtext',
            'label' => 'AX文件前缀',
            'width' => 110,
            'filtertype' => 'normal',
            'editable' => false,
            'in_list' => true,
            'default_in_list' => true,
        ),
    'ax_setting_info' =>
        array (
            'type' => 'longtext',
            'editable' => false,
            'label' => 'AX配置信息',
            'width' => 500,
            'in_list' => true,
            'default_in_list' => true,
        ),
    ),
    'comment' => 'AX配置表',
    'engine' => 'innodb',
    'version' => '$Rev: 44513 $',
);