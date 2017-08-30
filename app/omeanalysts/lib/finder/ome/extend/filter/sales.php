<?php
class omeanalysts_finder_ome_extend_filter_sales{
    function get_extend_colums(){
        $db['ome_sales']=array (
            'columns' => array (
                'order_id' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            )
        );
        return $db;
    }

}