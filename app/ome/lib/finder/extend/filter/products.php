<?php
class ome_finder_extend_filter_products{
    function get_extend_colums(){
        $db['products']=array (
            'columns' => array (
                'brand_id' => array (
                    'type' => 'table:brand@ome',
                    'required' => true,
                    'label' => '品牌',
                    'editable' => true,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                ),
                'type_id' => array (
                    'type' => 'table:goods_type@ome',
                    'required' => true,
                    'label' => '类型',
                    'editable' => true,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                ),
                'branch_id' => array (
                    'type' => 'table:branch@ome',
                    'required' => true,
                    'label' => '仓库',
                    'editable' => true,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}

