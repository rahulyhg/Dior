<?php
class ome_branch_pos_to_import {

    function run(&$cursor_id,$params){

        $bpObj = &app::get($params['app'])->model($params['mdl']);
        $branchObj = &app::get('ome')->model('branch');
        $Sdf = array();
        $bp = array();
        $Sdf = $params['sdfdata'];

        foreach ($Sdf as $v){
            
            $bp = array();
            //获取仓库ID
            $branch = $branchObj->dump(array('name'=>trim($v[1])), 'branch_id');
            if ($branch['branch_id']){
                $bp['store_position'] = $v[0];
                $bp['branch_id'] = $branch['branch_id'];
                $bpObj->save($bp);
            }
        }
        return false;
    }
}
