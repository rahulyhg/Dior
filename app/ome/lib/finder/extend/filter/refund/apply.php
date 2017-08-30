<?php
class ome_finder_extend_filter_refund_apply{
    function get_extend_colums(){
        $problem = kernel::single('ome_mdl_return_product_problem');
        $problem_info = $problem->getList('problem_id,problem_name');
        $filter = array();
        if(!empty($problem_info)){
            foreach($problem_info as $v){
                $filter[$v['problem_id']] = $v['problem_name'];
            }
        }
        $db['refund_apply']=array (
            'columns' => array (
                'order_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'problem_id' => array (
                    'type' => $filter,
                    'label' => '售后类型',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => false,
                    'default_in_list' => false,
                ), 
            )
        );
        return $db;
    }
}