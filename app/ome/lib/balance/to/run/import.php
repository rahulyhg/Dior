<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_balance_to_run_import {
	  
    function run(&$cursor_id,$params){
		$o = app::get($params['app'])->model($params['mdl']);
		$sdfdata = $params['sdfdata'];
		$method = $params['method'];
		//error_log(var_export($sdfdata,true),3,'f:/alipay.txt');
        while( $v = array_shift( $sdfdata ) ){
			if($method=='second_cod'){
				$o->second_cod($v);
			}else{
				$o->balanceOfAccount($v);
			}
        }
		
        return 0;
	}
}