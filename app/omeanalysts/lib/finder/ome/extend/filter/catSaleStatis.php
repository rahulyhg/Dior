<?php
class omeanalysts_finder_ome_extend_filter_catSaleStatis{

    function get_extend_colums(){
        $db['cat_sale_statis'] = array (
            'columns' => array (
                'goods_type' => array (
                    'type' => 'table:goods_type@ome',
                    'label' => '商品类目',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}