<?php

class ome_mdl_dly_corp_area extends dbeav_model{

    function get_corp_area($corp_id,$areaGroupId){
        $corp=$this->getList('region_id',array('corp_id'=>$corp_id));
        $areaGroup=explode(",",$areaGroupId);
        $areaGroup = kernel::single('ome_region')->get_region_node($areaGroup);
        foreach($corp as $k=>$v){
            if(in_array($v,$areaGroup)==false){
                $this->Del_corp_area($corp_id,$v['region_id']);
            }
        }
        foreach($areaGroup as $key=>$value){
            if($value!=''){
                $sdf_area = array(
                    'corp_id'=>$corp_id,
                    'region_id'=>$value
                );
            $this->save($sdf_area);
            }
        }

    }
    function Del_corp_area($corp_id,$region_id){
        $this->db->exec('DELETE FROM sdb_ome_dly_corp_area WHERE corp_id='.$corp_id.' AND region_id='.$region_id);
    }
    
    function getCorpByRegionId($region_id){
        $sql = "SELECT dc.corp_id,dc.name FROM sdb_ome_dly_corp_area dca JOIN sdb_ome_dly_corp dc ON dca.corp_id=dc.corp_id WHERE dca.region_id=".$region_id;
        return $this->db->select($sql);
    }

}

?>
