<?php
$db['iso']=array (
  'columns' =>
  array (
   'iso_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
   'confirm' =>
    array (
      'type' => 'tinybool',
      'default' => 'N',
      'required' => true,
      'label' => '确认状态',
      'width' => 75,
      'hidden' => true,
      'editable' => false,
    ),
    'defective_status'=>array (
      'type' =>
      array (
        0 => '无需确认',
        1 => '未确认',
		      2 => '已确认',
      ),
      'default' => '0',
      'label' => '残损确认状态',
    ),
     'name' =>
    array (
      'type' => 'varchar(200)',
      'label' => '出入单名称',
      'width' => 160,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'iso_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '出入库单号',
      'is_title' => true,
      'default_in_list'=>true,
      'searchtype' => 'has',
	  'in_list'=>true,
      'width' => 125,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'out_iso_bn' =>
    array (
      'type' => 'varchar(32)',
    
      'label' => '外部出入库单号',
      'is_title' => true,
      'width' => 125,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'type_id' =>
    array (
      'type' => 'table:iostock_type@ome',
      'required' => true,
      'default_in_list'=>true,
	  'in_list'=>true,
      'comment' => '出入库类型id',
      'label' => '出入库类型',
      'filtertype' => 'has',
      'filterdefault' => true,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'required' => true,
      'label' => '仓库名称',
      'filtertype' => 'normal',
      'filterdefault' => true,
      'default_in_list'=>true,
	  'in_list'=>true,
    ),
    'original_bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '采购单单号',
      'searchtype' => 'has',
      'default_in_list'=>true,
      'in_list'=>true,
    ),
    'original_id' =>
    array (
      'type' => 'int unsigned',
      'comment' => '原始单据id',
    ),
    'supplier_id' =>
    array (
      'type' => 'number',
      'comment' => '供应商id',
    ),
    'supplier_name' =>
    array (
      'type' => 'varchar(32)',
      'label' => '供应商名称',
      'comment' => '供应商名称',
    ),
    'product_cost' =>
    array (
      'type' => 'money',
      'label' => '商品总额',
      'width' => 75,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
    ),
    'iso_price' =>
    array (
      'type' => 'money',
      'label' => '出入库费用',
      'required' => true,
      'default' => 0,
	  'in_list'=>true,
    ),
    'cost_tax' =>
    array (
      'type' => 'money',
      'comment' => '税率',
    ),
    'oper' =>
    array (
      'type' => 'varchar(30)',
      'comment' => '经手人',
	  'in_list'=>true,
      'label' => '经手人',
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'comment' => '出入库时间',
      'filtertype' => 'time',
      'filterdefault' => true,
      'default_in_list'=>true,
	  'in_list'=>true,
      'label' => '出入库单生成',
    ),
    'operator' =>
    array (
      'type' => 'varchar(30)',
      'comment' => '操作人员',
      'default_in_list'=>true,
	  'in_list'=>true,
      'label' => '操作人员',
    ),
    'settle_method' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '结算方式',
      'label' => '结算方式',
    ),
    'settle_status' =>
    array (
      'type' => array(
        '0' => '未结算',
        '1' => '已结算',
        '2' => '部分结算',
      ),
      'label' => '结算状态',
    ),
    'settle_operator' =>
    array (
      'type' => 'varchar(30)',
      'comment' => '结算人',
      'label' => '结算人',
    ),
    'settle_time' =>
    array (
      'type' => 'time',
      'comment' => '结算时间',
      'label' => '结算时间',
    ),
  'complete_time' =>
  array (
          'type' => 'time',
          'comment' => '调拨出入库完成时间',
          'label' => '出入库完成时间',
  ),
    'settle_num' =>
    array (
      'type' => 'number',
      'comment' => '结算数量',
      'label' => '结算数量',
    ),
    'settlement_bn' =>
    array (
      'type' => 'varchar(32)',
      'comment' => '结算单号',
      'label' => '结算单号',
    ),
    'settlement_money' =>
    array (
      'type' => 'money',
      'comment' => '结算金额',
      'label' => '结算金额',
    ),
    'memo' =>
    array (
      'type' => 'text',
      'comment' => '备注',
      'label'=>'备注',
      'in_list'=>true,
    ),
    'emergency' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'label' => '是否紧急',
      'width' => 60,
      'editable' => false,
      'in_list' => true,
    ),
    'iso_status' =>
    array (
      'type' =>
      array (
        1 => '未出/入库',
        2 => '部分出/入库',
        3 => '全部出/入库',
        4 => '取消',
),
      'default' => 1,
      'label' => '出入库状态',
      'width' => 60,
      'editable' => false,
     'filtertype' => 'has',
	  'filterdefault' => true,
    ),
    'check_status' =>
    array (
      'type' =>
      array (
        1 => '未审',
        2 => '已审',

      ),
      'default' => 1,
      'label' => '审核状态',
      'width' => 60,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filterdefault' => true,
    ),
    'extrabranch_id' =>
    array (
      'type' => 'number',
      'label' => '外部仓库名称',
     'default' => 0,

    ),
    'corp_id' =>
    array (
      'type' => 'number',
      'comment' => '物流公司ID',
      'editable' => false,
      'label' => '物流公司',
      
    ),
  ),
  'index' =>
  array (
    'ind_iso_bn' =>
    array (
        'columns' =>
        array (
          0 => 'iso_bn',
        ),
    ),
    'ind_original_bn' =>
    array (
        'columns' =>
        array (
          0 => 'original_bn',
        ),
    ),
    'ind_original_id' =>
    array (
        'columns' =>
        array (
          0 => 'original_id',
        ),
    ),
    'ind_supplier_id' =>
    array (
        'columns' =>
        array (
          0 => 'supplier_id',
        ),
    ),
    'ind_create_time' =>
    array (
        'columns' =>
        array (
          0 => 'create_time',
        ),
    ),
  ),
  'engine' => 'innodb',
  'version' => '$Rev:  51996',
);