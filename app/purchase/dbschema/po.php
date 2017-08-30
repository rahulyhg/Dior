<?php
$db['po']=array (
  'columns' =>
  array (
    'po_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),

    'name' =>
    array (
      'type' => 'varchar(200)',
      'label' => '采购单名称',
      'width' => 160,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'po_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '采购单编号',
      'width' => 140,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'supplier_id' =>
    array (
      'type' => 'table:supplier',
      'required' => true,
      'label' => '供应商',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      //'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),

    'purchase_time' =>
    array (
      'type' => 'time',
      'label' => '采购日期',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),
    'amount' =>
    array (
      'type' => 'money',
      'label' => '金额总计',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'operator' =>
    array (
      'type' => 'varchar(50)',
      'label' => '采购员',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'po_type' =>
    array (
      'type' =>
      array (
        'cash' => '现购',
        'credit' => '赊购',
      ),
      'label' => '付款单 / 赊购单',
      'width' => 100,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),

    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'label' => '仓库',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),
    'arrive_time' =>
    array (
      'type' => 'time',
      'label' => '预计到货',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),
    'deposit' =>
    array (
      'type' => 'money',
      'label' => '预付款原款',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    'deposit_balance' =>
    array (
      'type' => 'money',
      'label' => '预付款金额',
      'width' => 110,
      'editable' => false,
    ),

    'product_cost' =>
    array (
      'type' => 'money',
      'label' => '商品总额',
      'width' => 75,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'delivery_cost' =>
    array (
      'type' => 'money',
      'label' => '物流费用',
      'width' => 75,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'eo_status' =>
    array (
      'type' =>
      array (
        0 => 'N/A',
        1 => '待入库',
        2 => '部分入库',
        3 => '已入库',
        4 => '未入库',
      ),
      'default' => 1,
      'label' => '入库状态',
      'width' => 60,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => false,
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
    'defective_status'=>array (
      'type' =>
      array (
        0 => '无需确认',
        1 => '未确认',
		      2 => '已确认',
      ),
      'default' => '0',
      'label' => '残损确认',
    ),
    'check_operator' =>
    array (
      'type' => 'varchar(50)',
      'label' => '审核人',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
  'op_name' =>
  array (
      'type' => 'varchar(50)',
      'label' => '采购单创建人',
      'default' => '',
      'width' => 90,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
  ),
    'check_time' =>
    array (
      'type' => 'time',
      'label' => '审核时间',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),
    'statement' =>
    array (
      'type' =>
      array (
        1 => '未结算',
        2 => '部分结算',
        3 => '已结算',
      ),
      'default' => 1,
      'label' => '结算状态',
      'width' => 60,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'po_status' =>
    array (
      'type' =>
      array (
        1 => '已新建',
        2 => '采购终止',
        3 => '采购退货',
        4 => '采购完成',
      ),
      'default' => 1,
      'label' => '采购状态',
      'width' => 60,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
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
   'out_iso_bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '外部采购单编号',
      'width' => 140,
     
     
    ),
  ),
  'index' =>
  array (
    'ind_po_bn' =>
    array (
      'columns' =>
      array (
        0 => 'po_bn',
      ),
    ),
    'ind_statement' =>
    array (
      'columns' =>
      array (
        0 => 'statement',
      ),
    ),
    'ind_po_status' =>
    array (
      'columns' =>
      array (
        0 => 'po_status',
      ),
    ),
    'ind_eo_status' =>
    array (
      'columns' =>
      array (
        0 => 'eo_status',
      ),
    ),
  ),
  'comment' => '采购单',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
