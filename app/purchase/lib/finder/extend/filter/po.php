<?php
class purchase_finder_extend_filter_po{
    function get_extend_colums(){
        $db['po']=array (
            'columns' => array (
                'bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '货号',
                    'width' => 85,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'barcode' => array (
                    'type' => 'varchar(32)',
                    'label' => '条形码',
                    'width' => 110,
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

