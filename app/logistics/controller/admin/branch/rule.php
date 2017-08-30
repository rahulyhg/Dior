<?php
    class logistics_ctl_admin_branch_rule extends desktop_controller {
        var $workground = 'setting_tools';
        var $defaultWorkground = 'setting_tools';

        /**
        *仓库对应规则列表
        */
        function saveBranchRule(){
            $data = $_POST;
            $branch_id = $data['branch_id'];
            $set_rule = $data['set_rule'];
            if($data){
                #保存信息至物流设置主表

                $result = $this->app->model('branch_rule')->create($data);

                $this->splash('success','index.php?app=logistics&ctl=admin_rule&act=ruleList&branch_id='.$branch_id,'保存成功');
            }





        }


    }

?>