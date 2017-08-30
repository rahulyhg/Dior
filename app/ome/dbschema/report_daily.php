<?php
$db['delivery']=array (
  'columns' =>
  array (
    'delivery_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',

    ),
    'idx_split' =>
    array(
        'type' => 'bigint',
        'required' => true,
        'label' => '订单内容',
        'comment' => '订单的大致内容',
        'editable' => false,
        'width' => 160,
        'in_list' => false,
        'default_in_list' => false,
        'default' => 0,
    ),
    'skuNum' =>
    array(
        'type' => 'number',
        'required' => true,
        'label' => '商品种类',
        'comment' => '商品种类数',
        'editable' => false,
        'in_list' => false,
        'default' => 0,
    ),
    'itemNum' =>
    array(
        'type' => 'number',
        'required' => true,
        'label' => '商品总数量',
        'comment' => '商品种类数',
        'editable' => false,
        'in_list' => false,
        'default' => 0,
    ),
    'bnsContent' =>
    array(
        'type' => 'text',
        'required' => true,
        'label' => '具体订单内容',
        'comment' => '具体订单内容',
        'editable' => false,
        'in_list' => false,
        'default' => '',
    ),
    'delivery_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '发货单号',
      'comment' => '配送流水号',
      'editable' => false,
      'width' =>140,
      'searchtype' => 'nequal',
      'filtertype' => 'yes',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'delivery_group' =>
    array (
      'type' => 'table:order_type@omeauto',
      'label' => '发货单分组',
      'comment' => '发货单分组',
      'editable' => false,
      'width' =>75,
      'in_list' => true,
    ),
    'sms_group' =>
    array (
      'type' => 'table:order_type@omeauto',
      'label' => '短信发送分组',
      'comment' => '短信发送分组',
      'editable' => false,
      'width' =>75,
      'in_list' => true,
    ),
    'member_id' =>
    array (
      'type' => 'table:members@ome',
      'label' => '会员用户名',
      'comment' => '订货会员ID',
      'editable' => false,
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'is_protect' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'label' => '是否保价',
      'comment' => '是否保价',
      'editable' => false,
      'in_list' => true,
    ),
	'cost_protect' =>
    array (
       'type' => 'money',
      'default' => '0',
      'label' => '保价费用',
      'required' => false,
      'editable' => false,
    ),
    'is_cod' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'required' => true,
      'label' => '是否货到付款',
      'editable' => false,
      'filtertype' => 'normal',
      'in_list' => true,
    ),
    'delivery' =>
    array (
      'type' => 'varchar(20)',
      'label' => '配送方式',
      'comment' => '配送方式(货到付款、EMS...)',
      'editable' => false,
      'in_list' => true,
      'width' =>65,
      'default_in_list' => true,
      'is_title' => true,
    ),
    'logi_id' =>
    array (
      'type' => 'table:dly_corp@ome',
      'comment' => '物流公司ID',
      'editable' => false,
      'label' => '物流公司',
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'logi_name' =>
    array (
      'type' => 'varchar(100)',
      'label' => '物流公司',
      'comment' => '物流公司名称',
      'editable' => false,
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'logi_no' =>
    array (
      'type' => 'varchar(50)',
      'label' => '物流单号',
      'comment' => '物流单号',
      'editable' => false,
      'width' =>110,
      'in_list' => true,
      'default_in_list' => true,
	  'filtertype' => 'normal',
      'filterdefault' => true,
	  'searchtype' => 'nequal',
    ),
    'logi_number' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 1,
      'editable' => false,
      'label' => '包裹总数',
      'comment' => '物流包裹总数',
    ),
    'delivery_logi_number' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'label' => '已发货包裹数',
      'comment' => '已发货物流包裹数',
    ),
    'ship_name' =>
    array (
      'type' => 'varchar(50)',
      'label' => '收货人',
      'comment' => '收货人姓名',
      'editable' => false,
      'searchtype' => 'tequal',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
      'sdfpath' => 'consignee/name',
    ),
    'ship_area' =>
    array (
      'type' => 'region',
      'label' => '收货地区',
      'comment' => '收货人地区',
      'editable' => false,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'width' =>130,
      'in_list' => true,
      'default_in_list' => true,
      'sdfpath' => 'consignee/area',
    ),
    'ship_province' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'sdfpath' => 'consignee/province',
    ),
    'ship_city' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'sdfpath' => 'consignee/city',
    ),
    'ship_district' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'sdfpath' => 'consignee/district',
    ),
    'ship_addr' =>
    array (
      'type' => 'varchar(100)',
      'label' => '收货地址',
      'comment' => '收货人地址',
      'editable' => false,
      'filtertype' => 'normal',
      'width' =>150,
      'in_list' => true,
      'sdfpath' => 'consignee/addr',
    ),
    'ship_zip' =>
    array (
      'type' => 'varchar(20)',
      'label' => '收货邮编',
      'comment' => '收货人邮编',
      'editable' => false,
      'width' =>75,
      'in_list' => true,
      'default_in_list' => true,
      'sdfpath' => 'consignee/zip',
    ),
    'ship_tel' =>
    array (
      'type' => 'varchar(30)',
      'label' => '收货人电话',
      'comment' => '收货人电话',
      'editable' => false,
      'in_list' => true,
      'sdfpath' => 'consignee/telephone',
    ),
    'ship_mobile' =>
    array (
      'type' => 'varchar(50)',
      'label' => '收货人手机',
      'comment' => '收货人手机',
      'editable' => false,
      'in_list' => true,
      'sdfpath' => 'consignee/mobile',
    ),
    'ship_email' =>
    array (
      'type' => 'varchar(150)',
      'label' => '收货人Email',
      'comment' => '收货人Email',
      'editable' => false,
      'in_list' => true,
      'sdfpath' => 'consignee/email',
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'label' => '单据创建时间',
      'comment' => '单据生成时间',
      'editable' => false,
      'filtertype' => 'time',
      'in_list' => true,
    ),
    'status' =>
    array (
      'type' =>
      array (
        'succ' => '已发货',
        'failed' => '发货失败',
        'cancel' => '已取消',
        'progress' => '等待配货',
        'timeout' => '超时',
        'ready' => '等待配货',
        'stop' => '暂停',
        'back' => '打回',
        'return_back'=>'退回',
      ),
      'default' => 'ready',
      'width' => 150,
      'required' => true,
      'comment' => '状态',
      'editable' => false,
      //'filterdefault' => true,
      //'filtertype' => 'yes',
      'label' => '发货状态',
      //'in_list' => true,
      //'default_in_list' => true,
    ),
      'is_received' =>
      array (
          'type' =>
          array (
                  '0'=>'否',
                  '1'=>'是',                
          ),
          'default' => '0',
          'label' => '是否签收',
          'comment' => '是否签收',
          'editable' => false,
          'width' =>110,
          'in_list' => true,
          'default_in_list' => false,
          'filtertype' => 'normal',
          'filterdefault' => true,
          'searchtype' => 'nequal',
      ),    
    'memo' =>
    array (
      'type' => 'longtext',
      'label' => '备注',
      'comment' => '备注',
      'editable' => false,
      'in_list' => true,
    ),
    'disabled' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'comment' => '无效',
      'editable' => false,
      'label' => '无效',
      'in_list' => true,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'editable' => false,
      'label' => '仓库',
      'width' => 110,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'panel_id' => 'delivery_finder_top',
    ),
    'stock_status' =>
    array (
      'type' => 'bool',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'width' => 75,
      'default' => 'false',
      'comment' => '配货单是否打印',
      'label' => '配货单打印',
      //'in_list' => true,
    ),
    'deliv_status' =>
    array (
      'type' => 'bool',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'required' => true,
      'editable' => false,
      'width' => 75,
      'default' => 'false',
      'comment' => '商品清单是否打印',
      'label' => '发货单打印',
      //'in_list' => true,
    ),
    'expre_status' =>
    array (
      'type' => 'bool',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'required' => true,
      'width' => 75,
      'editable' => false,
      'default' => 'false',
      'comment' => '快递单是否打印',
      'label' => '快递单打印',
      //'in_list' => true
    ),
    'verify' =>
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
      'comment' => '是否校验',
    ),
    'process' =>
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
      'comment' => '是否发货',
    ),
    'net_weight' =>
    array (
      'type' => 'money',
      'editable' => false,
      'comment' => '商品重量',
    ),
    'weight' =>
    array (
      'type' => 'money',
      'editable' => false,
      'comment' => '包裹重量',
    ),
    'last_modified' =>
    array (
      'label' => '最后更新时间',
      'type' => 'last_modify',
      'editable' => false,
      'in_list' => true,
    ),
    'delivery_time' =>
    array (
      'type' => 'time',
      'label' => '发货时间',
      'comment' => '发货时间',
      'editable' => false,
      'in_list' => true,
    ),
    'delivery_cost_expect' =>
    array (
      'type' => 'money',
      'default' => '0',
      'editable' => false,
      'comment' => '预计物流费用(包裹重量计算的费用)',
    ),
    'delivery_cost_actual' =>
    array (
      'type' => 'money',
      'editable' => false,
      'comment' => '实际物流费用(物流公司提供费用)',
    ),
    'parent_id' =>
    array (
      'type' => 'bigint unsigned',
      'editable' => false,
      'default' => 0
    ),
    'bind_key' =>
    array (
      'type' => 'varchar(32)',
      'editable' => false,
    ),
    'type' =>
    array (
      'type' =>
      array (
        'normal' => '普通发货单',
        'reject' => '拒绝退货的发货单',
      ),
      'default' => 'normal',
      'editable' => false,
      'editable' => false,
    ),
    'is_bind' =>
    array (
      'type' => 'bool',
      'required' => true,
      'editable' => false,
      'default' => 'false',
    ),
    'shop_id' =>
    array (
      'type' => 'table:shop@ome',
      'label' => '来源店铺',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'filtertype' => 'normal',
    ),
    'is_sync' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => 'false',
    ),
   'order_createtime' =>
    array (
      'type' => 'time',
      'label' => '订单创建时间',
      'width' => 130,
      'editable' => false,
      'filtertype' => 'time',
      'in_list' => false,
    ),
    'pause' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => 'false',
    ),
    'ship_time' =>
    array (
      'type' => 'varchar(50)',
      'editable' => false,
      'sdfpath' => 'consignee/r_time',
    ),
    'op_id' =>
    array (
      'type' => 'table:account@pam',
      'editable' => false,
      'required' => true,
    ),
    'op_name' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'has_checked' =>
    array (
      'type' => 'intbool',
      'default' => '0',
      'label' => '是否经过重复验证',
    ),
    'deli_cfg' => array(
        'type' => 'varchar(20)',
        'default' => '',
        'editable' => false,
        'required' => true,
    ),
	'print_status' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 0,
      'width' => 150,
      'required' => true,
      'comment' => '打印状态',
    ),
    'sync' =>
    array (
      'type' => array(
          'none' => '未请求',
          'fail' => '失败',
          'succ' => '成功',
      ),
      'default' => 'none',
      'label' => '同步状态',
      'editable' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
  
  ),
  'index' =>
  array (
    'ind_delivery_bn' =>
    array (
      'columns' =>
      array (
        0 => 'delivery_bn',
      ),
      'prefix' => 'unique',
    ),
    'ind_logi_no' =>
    array (
      'columns' =>
      array (
        0 => 'logi_no',
      ),
      'prefix' => 'unique',
    ),
    'ind_status' =>
    array (
      'columns' =>
      array (
        0 => 'status',
      ),
    ),
    'ind_stock_status' =>
    array (
      'columns' =>
      array (
        0 => 'stock_status',
      ),
    ),
    'ind_deliv_status' =>
    array (
      'columns' =>
      array (
        0 => 'deliv_status',
      ),
    ),
    'ind_expre_status' =>
    array (
      'columns' =>
      array (
        0 => 'expre_status',
      ),
    ),
    'ind_verify' =>
    array (
      'columns' =>
      array (
        0 => 'verify',
      ),
    ),
    'ind_parent_id' =>
    array (
      'columns' =>
      array (
        0 => 'parent_id',
      ),
    ),
    'ind_logi_id' =>
    array(
        'columns' =>
        array(
            0 => 'logi_id',
        ),
    ),
    'ind_process' =>
    array(
        'columns' =>
        array(
            0 => 'process',
        ),
    ),
    'ind_type' =>
    array(
        'columns' =>
        array(
            0 => 'type',
        ),
    ),
    'ind_bind_key' =>
    array(
        'columns' =>
        array(
            0 => 'bind_key',
        ),
    ),
    'ind_order_createtime' =>
    array(
        'columns' =>
        array(
            0 => 'order_createtime',
        ),
    ),
    'ind_delivery_time' =>
    array(
        'columns' =>
        array(
            0 => 'delivery_time',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev: 41996',
);