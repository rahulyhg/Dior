<?php
/**
 * 规则模型类
 *
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_mdl_regulation extends dbeav_model
{
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

    public function pre_recycle($rows) 
    {
        foreach ($rows as $key=>$row) {
            $rid[] = $row['regulation_id'];
        }
        $apply = $this->app->model('regulation_apply')->getList('id',array('regulation_id'=>$rid,'using'=>'true'),0,1);
        if ($apply) {
            $this->recycle_msg = '规则对应的应用已经开启，无法进行删除！';
            return false;
        }
        return true;
    }
}
