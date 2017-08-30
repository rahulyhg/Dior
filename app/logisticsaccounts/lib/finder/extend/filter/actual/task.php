<?php
class logisticsaccounts_finder_extend_filter_actual_task{
    
    function get_extend_colums(){
        $db['actual_task']=array (
            'columns' => array (
               
              'branch_id' =>
                    array (
                    'type' => 'table:branch@ome',
                    'editable' => false,
                    'label' => '仓库',
                    'width' => 110,
                    
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'actual_task_finder_top',
                ),
                
            )
        );
        
        if (kernel::single('desktop_user')->is_super()) {
           $db['actual_task']['columns'] = array(
                'op_id' =>
                array (
                  'type' => 'table:account@pam',
                  'label' => '创建人',
                  'editable' => false,
                  'width' => 60,
                  
                  'filtertype' => 'normal',
                  'filterdefault' => true,
                  'in_list' => true,
                  'default_in_list' => true,
                  'panel_id' => 'actual_task_finder_top',
                ),
            );
        }
       
        return $db;
    }
}
