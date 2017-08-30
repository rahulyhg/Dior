<?php
$db['purchase_refunds']=array (
  'columns' =>
  array (
    'refund_id' =>
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'editable' => false,
    ),
    'refund_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '退款单编号',
      'width' => 140,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
      'is_title' => true,
      'searchtype' => 'has',
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'rp_id' =>
    array (
      'type' => 'table:returned_purchase',
      'label' => '退货单ID',
      'width' => 140,
      'editable' => false,
    ),
     'add_time' =>
    array (
      'type' => 'time',
      'label' => '制单日期',
      'width' => 70,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'supplier_id' =>
    array (
      'type' => 'table:supplier',
      'label' => '供应商',
      'width' => 130,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'po_type' =>
    array (
      'type' =>
        array(
            'cash'=> '现购',
            'credit'=> '赊购',
        ),
      'label' => '采购方式',
      'width' => 90,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'type' =>
    array (
      'type' =>
        array(
            'po'=> '入库取消',
            'eo'=> '采购退货',
            'iso'=> '直接出库',
        ),
      'label' => '退货方式',
      'width' => 80,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'operator' =>
    array (
      'type' => 'varchar(50)',
      'label' => '经办人',
      'width' => 90,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'payment' =>
    array (
      'type' => 'table:payment_cfg@ome',
      'editable' => false,
    ),
    'paymethod' =>
    array (
      'type' => 'varchar(100)',
      'editable' => false,
    ),
    'refund' =>
    array (
      'type' => 'money',
      'label' => '结算金额',
      'width' => 90,
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
    ),
    'delivery_cost' =>
    array (
      'type' => 'money',
      'label' => '物流费用',
      'width' => 75,
      'default' => 0,
      'editable' => false,
      'in_list' => true,
    ),
    'op_id' =>
    array (
      'type' => 'table:account@pam',
      'editable' => false,
    ),
    'statement_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => '结算日期',
      'width' => 70,
      'default_in_list' => true,
      'in_list' => true,
      'filtertype' => 'time',
      'filterdefault' => true,
    ),
    'bank_no' =>
    array (
      'type' => 'varchar(50)',
      'label' => '银行账号',
      'width' => 110,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
    ),
    'statement_status' =>
    array (
      'type' =>
      array (
        1 => '未结算',
        2 => '已结算',
        3 => '拒绝结算',
      ),
      'default' => '1',
      'label' => '结算状态',
      'width' => 90,
      'required' => true,
      'editable' => false,
      'in_list' => true,
      'default_in_list' => false,
      'filtertype' => 'yes',
      'filterdefault' => true,
    ),
    'memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
  ),
  'index' =>
  array (
    'ind_refund_bn' =>
    array (
      'columns' =>
      array (
        0 => 'refund_bn',
      ),
    ),
    'ind_po_type' =>
    array (
      'columns' =>
      array (
        0 => 'po_type',
      ),
    ),
    'ind_statement_status' =>
    array (
      'columns' =>
      array (
        0 => 'statement_status',
      ),
    ),
  ),
  'comment' => '退款单',
  'engine' => 'innodb',
  'version' => '$Rev: 44513 $',
);
