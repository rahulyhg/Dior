<?php
/**
 * 解析 desktop.xml 的基类
 */

class desktop_application_prototype_xml extends base_application_prototype_xml {

	/**
	 * 是否强制更新
	 */
	static $force_update = false;

     	function last_modified($app_id){

        	if (self::$force_update) {
            		return -1;
        	} else {

            		return parent::last_modified($app_id);
        	}
    	}
}
