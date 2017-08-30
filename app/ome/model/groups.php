<?php

class ome_mdl_groups extends dbeav_model{

    
    /*
     * 根据组id来获取管理员信息
     * 
     * @param int $group_id 组id
     * @param string $cols 需要返回的列，默认'op_id,name'
     * 
     * @return mixed 符合条件的管理员信息数组或者false
     */
    function get_ops($group_id,$cols='user_id,name'){
        $columns = explode(',',$cols);
        if($columns && is_array($columns)){
            foreach($columns as $col){
                $c[] = "op.".$col;
            }            
            $sql = "SELECT ".implode(",",$c).",pam.login_name FROM sdb_desktop_users AS op 
                       LEFT JOIN sdb_ome_group_ops AS gop ON(op.user_id=gop.op_id)
                       LEFT JOIN sdb_pam_account AS pam ON (op.user_id=pam.account_id)
                       WHERE gop.group_id=".intval($group_id)." and op.status='1'";
               
            return $this->db->select($sql);
        }else{
            return false;
        }
    }

    /*
     * 根据管理员id来获取所属组信息
     * 
     * @param int $op_id 管理员id
     * @param string $cols 需要返回的列，默认'group_id'
     * 
     * @return mixed 符合条件的组信息
     */
    function get_group($op_id,$cols='group_id'){
        $columns = explode(',',$cols);
        if($columns && is_array($columns)){
            foreach($columns as $col){
                $c[] = "g.".$col;
            }
            $sql = "SELECT ".implode(",",$c)." FROM sdb_ome_groups AS g 
                        LEFT JOIN sdb_ome_group_ops AS gop ON(g.group_id=gop.group_id) 
                        WHERE gop.op_id=".intval($op_id);
            return $this->db->select($sql);
        }else{
            return array();
        }
    }
    
    /*
     * 获取有订单确认权限的用户列表
     * @params int $group_id 组id，默认为NULL，如果有id值则代表查出除这个组之外的所有有订单确认权限的用户
     */
    function get_confirm_ops($group_id=NULL){
        $roles = $this->db->select("SELECT * FROM sdb_desktop_roles");
        if($roles){
            $role_id = array();
            foreach($roles as $v){
                $workground = unserialize($v['workground']);
                if(in_array('order_confirm',$workground)){
                    $role_id[] = $v['role_id'];
//                    break;
                }
            }
            $ret = array();
            if($role_id){
                $sql = "SELECT distinct(du.user_id),du.name FROM sdb_desktop_users AS du 
                        LEFT JOIN sdb_desktop_hasrole AS dhr ON(du.user_id=dhr.user_id) 
                        LEFT JOIN sdb_desktop_roles AS dr ON(dhr.role_id=dr.role_id) 
                        WHERE dhr.role_id IN(".implode(",",$role_id).")";
                $user = $this->db->select($sql);
                foreach($user as $v){
                    if($group_id){
                        $where = " WHERE group_id <> ".intval($group_id);
                    }else{
                        $where = " WHERE 1 ";
                    }
                    $where .= " AND op_id=".$v['user_id'];
                    
                    if($this->db->selectrow("SELECT * FROM sdb_ome_group_ops ".$where)){
                        $ret[] = $v;
                    }
                }
            }
            
            return $ret;
        }else{
            return false;
        }
    }
    #删除订单确认逻辑判断
    function checkedGourpInfo($arr_group_id,&$msg=null){
        $str_group_id = implode(',',$arr_group_id);
        #检测操作员
        $_sql = 'select count(*) count from sdb_ome_group_ops where group_id in ('.$str_group_id.')';
        $_re = $this->db->selectrow($_sql);
        if($_re['count']){
            $msg = '操作员有绑定过订单确认小组，不能删！';
            return false;
        }
        #检测订单自动分派规则
        $_sql2 = 'select count(*) count from sdb_omeauto_autodispatch where group_id in ('.$str_group_id.')';
        $_re2 = $this->db->selectrow($_sql2);
        if($_re2['count']){
            $msg = '自动分派规则有绑定过订单确认小组，不能删！';
            return false;
        }
        #检测历史订单
        $_sql3 = 'select count(*) count FROM sdb_ome_orders where group_id in ('.$str_group_id.')';
        $_re3 = $this->db->selectrow($_sql3);
        if($_re3['count']){
          $msg = '订单记录中关联过订单确认小组，不能删！';
          return false;
       }
       return true;
    }
}
?>