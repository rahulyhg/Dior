<?php

class ome_mdl_roles extends dbeav_model{

    /**
     * 获取当前用户的权限
     *
     * @return  $acts
     */
    function getUserroles()
    {
        //如果是超级管理员，则返回->get_name()
        if (kernel::single('desktop_user')->is_super())
        {
            return array('is_super' => true);
        }
        $usr_permissions = $this->getPermissions(kernel::single('desktop_user')->has_roles());
        $return = array();
        foreach ($usr_permissions as $permission)
        {
            $return = array_merge($return,$this->getactroles($permission));
        }
        return $return;
    }

    function get_all_permits()
    {
        $record_roles = $this->db->select("SELECT * from sdb_ome_roles where disabled='false'");
        foreach ($record_roles as $onerole)
        {
            $return[$onerole['role']][$onerole['permission']] = true;
        }
        return $return;
    }

    //通过role获得permission，这个函数应该是系统提供的
    function getPermissions($roles)
    {
        //角色与permission的对应关系。
        $return = array();
        $record_roles = $this->db->select("SELECT * from sdb_ome_roles where disabled='false' and `role` in (".implode(',',$roles).")");
        foreach ($record_roles as $onerecord)
        {
            $return[] = $onerecord['permission'];
        }
        $return = array_unique($return);
        return $return;
    }

    //返回每一个角色对应本App的可操作权限。
    function getactroles($permission)
    {
        //每个permission与本App的可操作权限对应关系。
        //这是每个App自己管理的权限。
        $return = array();
        $record_roles = $this->db->select("SELECT * from sdb_ome_role_acts where disabled='false' and `permission` = '$permission'");
        foreach ($record_roles as $oneact)
        {
            $return[] = $oneact['act'];
        }
        return $return;
    }
}
?>