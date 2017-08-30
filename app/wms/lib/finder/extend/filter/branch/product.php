<?php
class wms_finder_extend_filter_branch_product{
    function get_extend_colums(){
        $db['branch_product']=array (
            'columns' => array (
              'bn'=>  array (
                    'type' => 'varchar(40)',
                    'editable' => false,
                    'label' => '货号',
                    'width' => 110,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                     'in_list' => true,
                    'panel_id' => 'wms_branch_finder_top',
                ),    
            'actual_store'=>array(
                    'type' => 'skunum',
                    'filtertype' => 'normal',
                    'required' => true,
                    'label' => '真实库存',
                  
                    'editable' => false,
                    'in_list' => true,
         
                    'default' => 0,
                    'filterdefault' => true,
                    'panel_id' => 'wms_branch_finder_top',
                ),
            'enum_store'=>array(
                    'type' => 'skunum',
                    'filtertype' => 'normal',
                    'required' => true,
                    'label' => '可用库存',
                    'editable' => false,
                    'in_list' => true,
                    'default' => 0,
                    'filterdefault' => true,
                    'panel_id' => 'wms_branch_finder_top',
                ),
            ),
       );
        return $db;
    }
}

?>