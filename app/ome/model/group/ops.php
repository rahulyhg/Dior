<?php

class ome_mdl_group_ops extends dbeav_model{

    /*
     *
     */
    function del_group_ops($op_id,$group_id){
        $this->db->exec("delete from sdb_ome_group_ops WHERE op_id='$op_id' AND group_id='$group_id'");
    }
}
?>