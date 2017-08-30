<?php
class sales_finder_extend_filter_aftersale{
    function get_extend_colums(){
        $oProblem = &app::get('ome')->model('return_product_problem');
        $problem_lists = $oProblem->getList('problem_id,problem_name',array('disabled'=>'false'));
        foreach ($problem_lists as $v) {
            $problem_list[$v['problem_id']] = $v['problem_name'];
        }
        
        $db['aftersale']=array (
            'columns' => array (
                'order_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'order'=>2,
                    'default_in_list' => true,
                ),              
                'member_uname' => 
                array(
                  'type' => 'varchar(30)',
                  'required' => false,
                  'editable' => false,
                  'label' => '用户名',
                  'in_list' => true,
                  'default_in_list' => true,
                  'order' => 5,
                  'filtertype' => 'normal',
                  'filterdefault' => true,
                  'width' => 130,
                ),
                'return_bn' => 
                array(
                    'type' => 'varchar(30)',
                    'label' => '售后申请单号',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'order'=>7,
                ),
                'reship_bn' => 
                array(
                    'type' => 'varchar(30)',
                    'label' => '退换货单号',
                    'width' => 140,
                    'searchtype' => 'has',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>8,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'return_apply_bn' => 
                array(
                    'type' => 'varchar(30)',
                    'label' => '退款申请单号',
                    'width' => 140,
                    'searchtype' => 'has',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'order'=>9,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'payment' =>
                array(
                  'type' => 'table:payment_cfg@ome',
                  'editable' => false,
                  'label' => '退款支付方式',
                  'filtertype' => 'normal',
                  'filterdefault' => true,
                ),
                'problem_id'=>
                array(
                  'type' => $problem_list,
                  'editable' => false,
                  'label' => '售后服务类型',
                  'filtertype' => 'normal',
                  'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}