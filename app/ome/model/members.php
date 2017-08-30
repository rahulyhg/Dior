<?php

class ome_mdl_members extends dbeav_model{
    
    /**
    * 快速查询主表信息
    * @access public
    * @param mixed $filter 过滤条件
    * @param String $cols 字段名
    * @return Array 会员信息
    */
    function getRow($filter,$cols='*'){
        if (empty($filter)) return array();
        
        $wheresql = '';
        if (is_array($filter)){
            foreach ($filter as $col=>$value){
                $wheresql[] = '`'.$col.'`=\''.$value.'\'';
            }
            $wheresql = implode(' AND ', $wheresql);
        }else{
            $wheresql = '`member_id`='.$filter;
        }
        $sql = sprintf('SELECT %s FROM `sdb_ome_members` WHERE %s',$cols,$wheresql);
        $row = $this->db->selectrow($sql);
        return $row;
    }

    function member_detail($member_id){
        $member_detail = $this->dump($member_id);
        return $member_detail;
    }

    
    function get_member($data,$col='uname'){
        if ($col == 'mobile'){
            $sql = "SELECT member_id,uname,area,mobile,email,sex FROM `sdb_ome_members` WHERE mobile LIKE '".$data."%'";
        }else {
            $sql = "SELECT member_id,uname,area,mobile,email,sex FROM `sdb_ome_members` WHERE uname LIKE '".$data."%'";
        }       
        $rows = $this->db->select($sql);
        return $rows;
    }
            
}
?>