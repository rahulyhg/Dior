<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class eccommon_view_compiler{

    function compile_modifier_cur($attrs,&$compile) {
        if(!strpos($attrs,',') || false!==strpos($attrs,',')){
            return $attrs = 'app::get(\'eccommon\')->model(\'currency\')->changer('.$attrs.')';
        }
    }

	public function compile_modifier_cur_name($attrs,&$compile) {
		 if(!strpos($attrs,',') || false!==strpos($attrs,',')){
            return $attrs = 'app::get(\'eccommon\')->model(\'currency\')->get_cur_name('.$attrs.')';
        }
	}

	public function compile_modifier_pay_name($attrs) {
        //todo 需要将货币汇率也缓存
        if(!strpos($attrs,',') || false!==strpos($attrs,',')){
            return $attrs = 'app::get(\'eccommon\')->model(\'payment_cfgs\')->get_app_display_name('.$attrs.')';
        }
    }

	public function compile_modifier_operactor_name($attrs) {
		if (!strpos($attrs,',') || false!==strpos($attrs,',')){
			return $attrs = 'app::get(\'pam\')->model(\'account\')->get_operactor_name('.$attrs.')';
		}
	}
}
