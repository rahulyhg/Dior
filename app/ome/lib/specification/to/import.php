<?php
class ome_specification_to_import {

    function run(&$cursor_id,$params,&$errmsg){

        $specObj = &app::get('ome')->model('specification');
        $specValObj = &app::get('ome')->model('spec_values');
        $specSdf = $params['sdfdata'];

        foreach ($specSdf as $v){
            $su = array();
            $su['spec_name'] = $v[0];
            $su['alias'] = $v[1];
            $su['spec_memo'] = $v[3];
            $spec_id = $specObj->save($su);

            if($spec_id && $v[2]){
                foreach(explode('|',$v[2]) as $spec){
                    $aSpecVal = array();
                    $aSpecVal['spec_id'] = $su['spec_id'];
                    $aSpecVal['spec_value'] = $spec;
                    $specValObj->save($aSpecVal);
					$m = $specValObj->db->errorinfo();
					if(!empty($m)){
						$errmsg.=$m.";";
					}
                }
            }


         }

        return false;
    }
}