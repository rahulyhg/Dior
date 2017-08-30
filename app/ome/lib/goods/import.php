<?php
class ome_goods_import {

    function run(&$cursor_id,$params,&$errmsg){
        $opObj  = &app::get('ome')->model('operation_log');
        $log_memo = '批量导入商品';
		 foreach($params['sdfdata'] as $v){
			$mdl = &app::get($params['app'])->model($params['mdl']);
            if(!$mdl->save($v)){
			$m = $mdl->db->errorinfo();
			kernel::log("errmsg = ".$m);
			if(!empty($m)){		
				$errmsg.=$m.";";
			}
			}
			$opObj->write_log('goods_import@ome', $v['goods_id'], $log_memo,'',$params['opinfo']);
        }
        return false;
	}
}