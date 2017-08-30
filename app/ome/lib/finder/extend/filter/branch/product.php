<?php
class ome_finder_extend_filter_branch_product{
    function get_extend_colums(){
        $brObj = &app::get('ome')->model('branch');

        $is_super = kernel::single('desktop_user')->is_super();
        
        if ($is_super){
            $branch_rows = $brObj->getList('branch_id,name','',0,-1);
        }else{
            $branch_rows = $brObj->getBranchByUser();
        }
        $branch_list = array();
        foreach($branch_rows as $branch){
            $branch_list [$branch['branch_id']] = $branch['name'];
        }
        $db['branch_product']=array (
            'columns' => array (
               'branch_id' =>
                    array (
                    'type' => $branch_list,
                    'editable' => false,
                    'label' => '仓库',
                    'width' => 110,
                    'default_value'=>$branch_rows[0]['branch_id'],
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'ome_branch_finder_top',
                ),
                'bn' =>
                    array (
                    'type' => 'varchar(40)',
                    'editable' => false,
                    'label' => '货号',
                    'width' => 110,
                  
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                 
                    'panel_id' => 'ome_branch_finder_top',
                ),
                 'actual_store' =>
                array(
                    'type' => 'skunum',
                    'filtertype' => 'normal',
                    'required' => true,
                    'label' => '真实库存',
                  
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'default' => 0,
                    'filterdefault' => true,
                    'panel_id' => 'ome_branch_finder_top',
                ),
                 'enum_store' =>
                array(
                    'type' => 'skunum',
                    'filtertype' => 'normal',
                    'required' => true,
                    'label' => '可用库存',
                
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'default' => 0,
                    'filterdefault' => true,
                    'panel_id' => 'ome_branch_finder_top',
                ),
            )
        );
        return $db;
     }


}