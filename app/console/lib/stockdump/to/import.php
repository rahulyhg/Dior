<?php
class console_stockdump_to_import {

    function run(&$cursor_id,$params){

        $stockObj = &app::get('console')->model('stockdump');
        $data = $params['sdfdata'];
        
        $options = array(
            'op_name'=>$data['op_name'],
            'from_branch_id'=>$data['from_branch_id'],
            'to_branch_id'=>$data['to_branch_id'],
        );

        $result = $stockObj->to_savestore($data['items'],$options);
        
        if ($result) {
            kernel::single('console_iostockdata')->notify_stockdump($result['stockdump_id'],'create');
        }
        return false;
    }
}
