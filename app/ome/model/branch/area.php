<?php

class ome_mdl_branch_area extends dbeav_model{

    /*
     * 删除已存在于地区仓库表但此次编辑勾掉的地区
     *
     * @param int $branch_id int $region_id
     *
     * @return bool
     */
    function Del_area($branch_id,$region_id){
        $this->db->exec('DELETE FROM sdb_ome_branch_area WHERE branch_id='.$branch_id.' AND region_id='.$region_id);
    }
    /*
     * 获取仓库绑定地区列表
     * @param int $branch_id
     *
     * @return array
     */
    function Get_region($branch_id){
        $sql = "SELECT region_id FROM sdb_ome_branch_area WHERE branch_id='$branch_id'";
        return $this->db->select($sql);
    }
     /*
     * 将传递进来的地区转换成地区列表
     *
     * @param array $areaGroupId
     *return $array
     */
    function Getregion_id($areaGroupId){
        foreach($areaGroupId as $key => $val){
                $tmp=explode(",",$val);
                unset($tmpGroupId);
                if (is_array($tmp)&&count($tmp)>0){
                    foreach($tmp as $k => $v){
                        if ($v){
                            if (strstr($v,"|")){
                                $regionId=substr($v,0,strpos($v,"|"));
                                //$tmpGroupId[] = $regionId;
                                $group_id=$this->getAllChild($regionId);
                                if (is_array($group_id)){
                                    foreach($group_id as $dk => $dv){
                                        $tmpGroupId[] = $dv;
                                    }
                                }
                            }else{
                                $tmpGroupId[]=$v;
                            }
                        }
                    }
                }
            }
            return $tmpGroupId;
    }

    function getAllChild($regionId){
        $row = kernel::single('eccommon_regions')->getAllChildById($regionId, 'containSelf');
        $Group = array();
        if (is_array($row)&&count($row)>0){
            foreach($row as $key => $val){
                $Group[]=$val['region_id'];
            }
        }
        return $Group;
    }

    function set_area($region_ids,$branch_id){
        $region_ids = explode(",",$region_ids);
        $region_ids = kernel::single('ome_region')->get_region_node($region_ids);
        foreach ($region_ids as $area_id){
            $data['branch_id'] = $branch_id;
            $data['region_id'] = $area_id;
            $this->save($data);
        }
    }
}

?>
