<?php

class ome_mdl_branch_groups extends dbeav_model{

    function del_branch_groups($branch_id,$group_id){
        $this->db->exec("delete from sdb_ome_branch_groups WHERE branch_id='$branch_id' AND group_id='$group_id'");
    }
}
?>