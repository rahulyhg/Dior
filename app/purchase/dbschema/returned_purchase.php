<?php
$db['returned_purchase']=array (
  'columns' =>
  array (
    'rp_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),

    'rp_bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '退货单编号',
      'width' => 150,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),

    'name' =>
    array (
      'type' => 'varchar(200)',
      'label' => '退货单名称',
      'width' => 160,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'supplier_id' =>
    array (
      'type' => 'table:supplier',
      'label' => '供应商',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'operator' =>
    array (
      'type' => 'varchar(50)',
      'label' => '经办人',
      'width' => 75,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'returned_time' =>
    array (
      'type' => 'time',
      'label' => '退货日期',
      'width' => 80,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'purchase_time' =>
    array (
      'type' => 'time',
      'label' => '采购日期',
      'width' => 140,
      'editable' => false,
      'in_list' => false,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),
    'branch_id' =>
    array (
      'type' => 'table:branch@ome',
      'label' => '仓库',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
    /*'arrive_time' =>
    array (
      'type' => 'time',
      'label' => '到货日期',
      'width' => 80,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),*/
    'amount' =>
    array (
      'type' => 'money',
      'label' => '金额总计',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'product_cost' =>
    array (
      'type' => 'money',
      'label' => '商品总额',
      'width' => 75,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'delivery_cost' =>
    array (
      'type' => 'money',
      'label' => '物流费用',
      'width' => 75,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'logi_no' =>
    array (
      'type' => 'varchar(50)',
      'label' => '物流单号',
      'width' => 80,
      'editable' => false,
      'in_list' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'po_type' =>
    array (
      'type' =>
      array (
        'cash' => '现款',
        'credit' => '赊账',
      ),
      'required' => true,
      'label' => '采购方式',
      'width' => 70,
      'editable' => false,
      'in_list' => false,
      'default_in_list' => false,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => false,
    ),
    'rp_type' =>
    array (
      'type' =>
      array (
        'po' => '入库取消单',
        'eo' => '采购退货单',
        'iso'=> '直接出入库',
      ),
      'label' => '退货单类型',
      'width' => 80,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'object_id' =>
    array (
      'type' => 'number',
      'label' => '采购入库单ID',
      'width' => 130,
      'editable' => false,
    ),
    'emergency' =>
    array (
      'type' => 'bool',
      'default' => 'false',
      'label' => '是否特别退货',
      'width' => 60,
      'editable' => false,
      'in_list' => true,
    ),
    'return_status' =>
    array (
      'type' =>
      array (
        1 => '已新建',
        2 => '退货完成',
        3 => '出库拒绝',
        4=>'部分退货',
        5=>'取消出库',
        ),
      'default' => 1,
      'label' => '退货状态',
      'width' => 60,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
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
    'out_iso_bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '外部单编号',
      'width' => 140,
     
     
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
    'ind_po_type' =>
    array (
      'columns' =>
      array (
        0 => 'po_type',
      ),
    ),
    'ind_rp_type' =>
    array (
      'columns' =>
      array (
        0 => 'rp_type',
      ),
    ),
    'ind_object_id' =>
    array (
      'columns' =>
      array (
        0 => 'object_id',
      ),
    ),
  ),
  'comment' => '退货单',
  'engine' => 'innodb',
  'version' => '$Rev: 51996',
);
