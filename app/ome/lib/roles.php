<?php
class ome_roles{
    function show_group($user_id=NULL){
        $order_confirm_group = kernel::database()->select("SELECT group_id,name FROM sdb_ome_groups WHERE g_type='confirm'");
        $ret = "";
        
        if($order_confirm_group){
            $cur_group_id = NULL;
            if($user_id){
                $cur_group = kernel::database()->selectrow("SELECT group_id FROM sdb_ome_group_ops WHERE op_id=".intval($user_id));
                $cur_group_id = $cur_group['group_id'];
            }
            
            if(count($order_confirm_group) == 1){
                $ret = "该操作员属于 '".$order_confirm_group[0]['name']."'"."<input type='hidden' name='confirm_group' value='".$order_confirm_group[0]['group_id']."'>";
            }else{
                $ret = "请选择订单确认小组：<select name='confirm_group'>";
                foreach($order_confirm_group as $v){
                    if($cur_group_id && $cur_group_id==$v['group_id']){
                        $selected = "SELECTED";
                    }else{
                        $selected = NULL;
                    }
                    $ret .= "<option value='".$v['group_id']."' ".($selected?$selected:'').">".$v['name']."</option>";
                }
                $ret .= "</select>";
            }
        }
        return $ret;
    }
    
    function show_branch($user_id=NULL){
        $branch = kernel::database()->select("SELECT branch_id,name FROM sdb_ome_branch");
        $ret = "";
        
        if($branch){
            $cur_branch_id = NULL;
            if($user_id){
                $cur_branch = kernel::database()->select("SELECT branch_id FROM sdb_ome_branch_ops WHERE op_id=".intval($user_id));
                foreach($cur_branch as $key=>$value){
                    $cur_branch_id[] = $value['branch_id'];
                }             
            }
            
            if(count($branch) == 1){
                $ret = "该操作员将工作于仓库：'".$branch[0]['name']."'"."<input type='hidden' name='branch[]' value='".$branch[0]['branch_id']."'>";
            }else{
                $ret = "请选择仓库：";
                foreach($branch as $v){
                    if(in_array($v['branch_id'],(array)$cur_branch_id)){
                        $checked = "checked=checked";
                    }else{
                        $checked = NULL;
                    }
                    $ret .= "<input type='checkbox' name='branch[]' value='".$v['branch_id']."' ".($checked?$checked:'')."/>".$v['name'];
                }
            }
        }
        return $ret;
    }
    
    function save_role($user_id,$data){
        $group_id = $data['confirm_group'];
        $branch_ids = $data['branch'];
        if($group_id){
            $user_count = app::get('ome')->model('group_ops')->count(array('op_id'=>$user_id));       
            if($user_count == 1){
            	app::get('ome')->model('group_ops')->update(array('group_id'=>$group_id),array('op_id'=>$user_id));
            }else{
                $t_data = array('group_id'=>$group_id,'op_id'=>$user_id);
                app::get('ome')->model('group_ops')->save($t_data);
            }
        }           
        app::get('ome')->model('branch_ops')->delete(array('op_id'=>$user_id));
        if($branch_ids){
            foreach($branch_ids as $branch_id){
                $t_data = array('branch_id'=>$branch_id,'op_id'=>$user_id);
                app::get('ome')->model('branch_ops')->save($t_data);
            }
        }
    }
}