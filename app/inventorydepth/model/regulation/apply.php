<?php
/**
 * 规则应用模型类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_mdl_regulation_apply extends dbeav_model 
{
    public $defaultOrder = 'priority asc';

    public function modifier_using($row) 
    {
        $using = '';
        if ($row == 'true') {
            $using = '<span style="color:green;">已启用</span>';
        } else {
            $using = '<span style="color:red;">未启用</span>';
        }

        return $using;
    }
}
