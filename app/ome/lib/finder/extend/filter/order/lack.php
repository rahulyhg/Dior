<?php
class ome_finder_extend_filter_order_lack{
    function get_extend_colums(){
        $oBranch = &app::get('ome')->model('branch');
        $branch_list = $oBranch-> getOnlineBranchs('branch_id,name');
        $branchRow = array();
        foreach ($branch_list as $branch ) {
            $branchRow[$branch['branch_id']] = $branch['name'];
        }
        $db['order_lack']=array (
            'columns' => array (
                 'shop_id' =>
                array (
                  'type' => 'table:shop@ome',
                  'label' => '来源店铺',
                  'width' => 75,
                  'editable' => false,
                'filtertype' => 'normal',
                  'filterdefault' => true,
                ),
                'branch_id' =>
                    array (
                   'type' => $branchRow,
                    'editable' => false,
                    'label' => '仓库',
                    'width' => 110,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'stock' =>
                array(
                    'type' => 'skunum',
                    'filtertype' => 'normal',
                    'required' => true,
                    'label' => '库存可用',
                    'comment' => '库存可用',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'default' => 0,
                    'filterdefault' => true,
                    'panel_id' => 'orderlack_finder_top',
                ),
               'product_lack' =>
                array(
                    'type' => 'skunum',
                    'filtertype' => 'normal',
                    'required' => true,
                    'label' => '缺货数量',
                    'comment' => '缺货数量',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'default' => 0,
                    'filterdefault' => true,
                    'panel_id' => 'orderlack_finder_top',
                ),
                'type_id' =>
                array (
                    'type' => 'table:goods_type@ome',
                    'sdfpath' => 'type/type_id',
                    'label' => '类型',
                    'width' => 100,
                    'editable' => false,
                    'filterdefalut' => true,
                    'filtertype' => 'yes',
                ),
               'brand_id' =>
                array (
                    'type' => 'table:brand@ome',
                    'sdfpath' => 'brand/brand_id',
                    'label' => '品牌',
                    'width' => 75,
                    'editable' => false,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                ),
             )
        );
        return $db;
    }
}

?>