<?php
class ome_finder_extend_filter_orders{
    function get_extend_colums(){
        $pay_type = ome_payment_type::pay_type();
        $cfgObj = &app::get('ome')->model('payment_cfg');
        $payments = $cfgObj->getList('*');
        $pay_bn = array();
        foreach($payments as $payment){
            $pay_bn[$payment['pay_bn']] = $payment['custom_name'];
        }
        $db['orders']=array (
            'columns' => array (
                'ship_status' => array (
                    'type' => array (
                        0 => '未发货',
                        1 => '已发货',
                        2 => '部分发货',
                        3 => '部分退货',
                        4 => '已退货'
                    ),
                    'default' => '0',
                    'required' => true,
                    'label' => '发货状态',
                    'width' => 75,
                    'editable' => false,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
                'shipping' =>
                array (
                        'type' => 'varchar(100)',
                        'label' => '配送方式',
                        'width' => 75,
                        'editable' => false,
                        'filtertype' => 'yes',
                        'filterdefault' => true,
                        'in_list' => true,
                ),
                'is_cod' =>
                array (
                        'type' => 'bool',
                        'required' => true,
                        'default' => 'false',
                        'editable' => false,
                        'label' => '货到付款',
                        'in_list' => true,
                        'width' => 60,
                        'filtertype' => 'yes',
                        'filterdefault' => true,
                ),
                'ship_tel_mobile' => array (
                    'type' => 'varchar(30)',
                    'label' => '收货人联系电话',
                    'comment' => '收货人联系电话',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'in_list' => true,
                ),
                'pay_type' => array (
                    'type' => $pay_type,
                    'label' => '支付类型',
                    'width' => 65,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
                'pay_bn' => array (
                    'type' => $pay_bn,
                    'label' => '支付方式',
                    'width' => 65,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
                'member_uname' => array (
                  'type' => 'varchar(50)',
                  'label' => '会员用户名',
                  'width' => 75,
                  'editable' => false,
                  'filtertype' => 'normal',
                  'filterdefault' => 'true',
                  'in_list' => true,
                  'default_in_list' => true,
                ),
                'product_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '货号',
                    'width' => 85,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'lettering_type' => array (
                    'type' =>
                        array (
                            0 => '普通',
                            1 => '图案',
                        ),
                    'label' => '刻字类型',
                    'width' => 85,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'product_barcode' => array (
                    'type' => 'varchar(32)',
                    'label' => '条形码',
                    'width' => 110,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'is_tax_no' => array(
				      'type' =>
				      array (
				        0 => '否',
				        1 => '是',
				      ),
                    'label' => '是否录入发票号',
                    'width' => 100,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
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
                
                'order_source' => array(
                  'type' => ome_mdl_orders::$order_source,
                  'label' => '订单类型',
                  'filtertype' => 'yes',
                  'filterdefault' => true,
                  'in_list' => true,
                  'default_in_list' => true,
                ),
                'paytime' => array(
                    'type'  => 'time',
                    'label' => '付款时间',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'tax_company' => array(
                    'type'  => 'varchar(255)',
                    'label' => '发票抬头',
                    'width' => 100,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'mark_text' =>array (
                    'type' => 'longtext',
                    'label' => '客服备注',
                    'width' => 100,
                    'editable' => false,
                    'in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'default_in_list'=>true
                ),
                    )
        );
        #只有财务那边的才用这个签收
        if($_GET['ctl'] != 'admin_finance'){
            unset($db['orders']['columns']['is_received']); 
        }
        return $db;
    }
}

