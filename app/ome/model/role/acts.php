<?php

class ome_mdl_role_acts extends dbeav_model{

    function get_user_roles($user_id)
    {
        return $this->getList("*",array("user_id"=>$user_id));
    }

    function clearuserpermissions($user_id)
    {
        $this->db->exec("update sdb_ome_role_acts set `disabled` = 'true' where `user_id` = ".intval($user_id));
    }
}
?>