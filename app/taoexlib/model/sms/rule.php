<?php

class taoexlib_mdl_sms_rule extends dbeav_model 
{

    
    /**
     * 根据规则id获取绑定关系信息
     *
     * @param  $rule_id
     * @return void
     * @author 
     **/
    public function getBindByRuleId($rule_id)
    {
    	$res = app::get('taoexlib')->model('sms_bind')->select()->columns()->where('tid=?',$rule_id)->instance()->fetch_row();
    	return $res;
    }
}