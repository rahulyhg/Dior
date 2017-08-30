<?php
class purchase_finder_extend_filter_products{
    function get_extend_colums(){
            $db['products']=array (
              'columns' =>
              array (
                'branch_id' =>
                array (
                  'type' => 'table:branch@ome',
                  'required' => true,
                  'default' => 0,
                  'label' => '仓库',
                  'width' => 75,
                  'editable' => true,
                  'filtertype' => 'yes',
                  'filterdefault' => true,
                  'in_list' => true,
                  'default_in_list' => true,
                ),
              ));
        return $db;
    }
}

