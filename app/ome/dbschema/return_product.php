<?php
$db['return_product']=array (
  'columns' => 
  array (
    'return_id' => 
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
      'label' => '售后ID',
      'comment' => '售后ID',      
    ),
    'return_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '退货记录流水号',
      'comment' => '退货记录流水号',
      'editable' => false,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'order_id' => 
    array (
      'type' => 'table:orders@ome',
      'label' => '订单号',
      'comment' => '订单号',
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
    ),
    'title' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'in_list' => true,
      'label' => '退货记录标题',
    ),
    'content' => 
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '申请售后原因',
      'comment' => '申请售后原因',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'attachment' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
      'label' => '附件',
      'comment' => '附件',        
    ),
    'product_data' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'comment' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '申请售后留言',
      'comment' => '申请售后留言',       
    ),
    'add_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '申请时间',
      'filtertype' => 'time',
      'default_in_list' => true,
      'in_list' => true,
      'width' => 130,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => '是否启用',
      'comment' => '是否启用',       
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'editable' => false,
      'label' => '店铺',
      'in_list' => true,
    ),
    'member_id' => 
    array (
      'type' => 'table:members@ome',
      'editable' => false,
    ),
    'process_data' =>
    array (
      'type' => 'longtext',
      'editable' => false,
      'comment' => '存储售后商品信息和仓库收货信息等',       
    ),
    'memo' => 
    array (
      'type' => 'longtext',
      'editable' => false,
      'label' => '备注',
      'comment' => '备注',         
    ),
    'money' =>
    array (
      'type' => 'money',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'label' => '最后合计金额',
      'width' => 90,
    ),
    'op_id' => 
    array (
      'type' => 'table:account@pam',
      'editable' => false,
      'label' => '操作员ID',
      'comment' => '操作员ID',        
    ),
    'refundmoney' =>
    array (
      'type' => 'money',
      'editable' => false,
      'label' => '退款金额',
      'in_list' => true,
      'default_in_list' => true,
      'width' => 75,
    ),
    'delivery_id' =>
    array (
      'type' => 'table:delivery@ome',
      'editable' => false,
      'label' => '发货单号',
      'in_list' => false,
      'default_in_list' => false,
      'width' => 150,
    ),
    'status' =>
    array (
      'type' => 
      array (
        1 => '申请中',
        2 => '审核中',
        3 => '接受申请',
        4 => '完成',
        5 => '拒绝',
        6 => '已收货',
        7 => '已质检',
        8 => '补差价',
        9 => '已拒绝退款',
      ),
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'label' => '状态',
      'in_list' => true,
      'default_in_list' => true,
      'required' => true,
      'default' => '1',
      'width' => 75,
    ),
    'last_modified' => 
    array (
      'label' => '最后更新时间',
      'type' => 'last_modify',
      'width' => 130,
      'editable' => false,
      'default_in_list' => true,
      'in_list' => true,
    ),
    'tmoney' =>
    array (
      'type' => 'money',
      'editable' => false,
      'label' => '退款的金额',      
    ),
    'bmoney' =>
    array (
      'type' => 'money',
      'editable' => false,
      'label' => '补差的金额',      
    ),
    'problem_id' =>
    array (
      'type' => 'table:return_product_problem@ome',
      'label' => '售后类型',
      'comment' => '售后类型',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'recieved' =>
    array(
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,      
      'comment'=>'是否收货',
      'label'=>'是否收货',      
    ),
    'verify' =>
    array(
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,      
      'comment'=>'是否质检',
      'label'=>'是否质检',      
    ),
     'source' =>
    array (
      'type' => 'varchar(50)',
      'default' => 'local',
      'editable' => false,
      'label' => '来源',
      'in_list' => true,
      'filterdefault' => true,
    ),
   
    'shop_type' =>
    array (
      'type' => 'varchar(50)',
      'label' => '店铺类型',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'outer_lastmodify' =>
    array (
      'label' => '前端店铺最后更新时间',
      'type' => 'time',
      'width' => 130,
      'editable' => false,
    
    ),
    'archive' =>
    array (
      'type' => 'tinyint unsigned',
      'required' => true,
      'default' => 0,
      'editable' => false,
    ),
     'is_fail' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => '失败售后单',
    ),
  ),
  'index' =>
  array (
    'ind_return_bn_shop' =>
    array (
        'columns' =>
        array (
          0 => 'return_bn',
          1 => 'shop_id',
        ),
        'prefix' => 'unique',
    ),
    'ind_return_bn' =>
    array (
        'columns' =>
        array (
          0 => 'return_bn',
        ),
    ),
    'ind_add_time' =>
    array (
        'columns' =>
        array (
          0 => 'add_time',
        ),
    ),    
  ), 
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  'comment'=>'售后申请表',
);