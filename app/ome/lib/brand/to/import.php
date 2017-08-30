<?php
class ome_brand_to_import {

    function run(&$cursor_id,$params,&$errmsg){

        $brandObj = &app::get('ome')->model('brand');
        $brandSdf = $params['sdfdata'];

        foreach ($brandSdf as $v){
            $su = array();
            $su['brand_name'] = $v[0];
            $su['brand_url'] = $v[1];
            $su['brand_keywords'] = $v[2];
          
            $brandObj->save($su);
			$m = $brandObj->db->errorinfo();
			if(!empty($m)){
				$errmsg.=$m.";";
			}
         }
         
        return false;
    }
}
