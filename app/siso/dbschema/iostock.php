<?php
$db['iostock']=array (
  'columns' =>
  array (
    'iostock_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'pkey' => true,
    ),
    'iostock_bn' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'label' => '出入库单号',
      'is_title' => true,
      'default_in_list'=>true,
      'in_list'=>true,
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
       'panel_id' => 'iostock_finder_top',
    ),
    'original_bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '原始单据号',
      'default_in_list'=>true,
	  'in_list'=>true,
    ),
    'original_id' =>
    array (
      'type' => 'int unsigned',
      'comment' => '原始单据id',
    ),
    'original_item_id' =>
    array (
      'type' => 'int unsigned',
      'comment' => '原始单明细id',
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
    'bn' =>
    array (
      'type' => 'varchar(32)',
      'label' => '货号',
      'required' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'default_in_list'=>true,
	  'in_list'=>true,
    ),
    'iostock_price' =>
    array (
      'type' => 'money',
      'label' => '出入库价格',
      'required' => true,
      'default' => 0,
	  'in_list'=>true,
    ),
    'nums' =>
    array (
      'type' => 'number',
      'label' => '出入库数量',
      'required' => true,
      'default_in_list'=>false,
	  'in_list'=>false,
      'filtertype' => 'number',
      'filterdefault' => true,
    ),
    'balance_nums' =>
    array (
      'type' => 'number',
      'label' => '库存结余',
      'required' => true,
      'default_in_list'=>true,
	  'in_list'=>true,
      'filtertype' => 'number',
      'filterdefault' => true,
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
      'label' => '出入库时间',
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
    'unit_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '单位成本',
      'label'=>'单位成本',
      'required' =>true,
      'in_list'=>true,
    ),
    'inventory_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '库存成本',
      'label'=>'库存成本',
      'in_list'=>true,
    ),
    'now_num' =>
    array (
      'type' => 'mediumint(8)',
      'default' => 0,
      'comment' => '结存数量',
      'label'=>'结存数量',
      'in_list'=>true,
    ), 
    'now_unit_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '结存单位成本',
      'label'=>'结存单位成本',
      'in_list'=>true,
    ), 
    'now_inventory_cost' =>
    array (
      'type' => 'decimal(20,3)',
      'default' => '0.000',
      'comment' => '结存库存成本',
      'label'=>'结存库存成本',
      'in_list'=>true,
    ),         
  ),
  'index' =>
  array (
    'ind_iostock_bn' =>
    array (
        'columns' =>
        array (
          0 => 'iostock_bn',
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
    'ind_original_item_id' =>
    array (
        'columns' =>
        array (
          0 => 'original_item_id',
        ),
    ),
    'ind_supplier_id' =>
    array (
        'columns' =>
        array (
          0 => 'supplier_id',
        ),
    ),
    'ind_bn' =>
    array (
        'columns' =>
        array (
          0 => 'bn',
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
  'version' => '$Rev:  $',
);