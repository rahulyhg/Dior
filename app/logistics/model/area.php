<?php
class logistics_mdl_area extends dbeav_model{

    var $defaultOrder = array('ordernum','DESC');

    function detailArea($area_id){
        $area = $this->getlist('area_id,local_name,region_id,ordernum,disabled',array('area_id'=>$area_id),0,1);
        $area = $area[0];
        $db = kernel::database();
        $region_name=array();

        $tmp_region_ids = explode(',', $area['region_id']);
        $region = kernel::single('eccommon_regions')->getListByIds($tmp_region_ids);

        foreach($region as $k=>$v){
            if($v!=''){
            $region_name[]=$v['local_name'];
            }
        }
        $area['region_name'] = implode(',',$region_name);
        return $area;

    }


    function getArea(){
        $db = kernel::database();
        $area = $this->getlist('area_id,local_name,region_id',0,-1);
        foreach($area as $k=>$v){
            $region_id = $v['region_id'];
            if($region_id){
                $tmp_region_ids = explode(',', $region_id);
                $region = kernel::single('eccommon_regions')->getListByIds($tmp_region_ids);
                $area[$k]['area_items'] = $region;
            }


        }
        return $area;

    }

    function getRegion($region_id){
        $region = kernel::single('eccommon_regions')->getListByIds($region_id);
        $region_list = array();
         foreach($region as $k=>$v){
             $region_list[] = $v['local_name'];
         }

         return implode(',',$region_list);
    }

    function getAllChild($region_id){
        $row = kernel::single('eccommon_regions')->getAllChildById($region_id,'uncontainSelf');
        $region_list = array();
        if (is_array($row)&&count($row)>0)
        {
            foreach ($row as $key => $val)
            {
                $region_list[$key]['region_id'] = $val['region_id'];
                $region_list[$key]['region_name'] = $val['local_name'];
            }
            return $region_list;
        }
    }

    /**
    *获取指定区域路径名称
    */
    function getRegionPath($region_id){
        $regionLib = kernel::single('eccommon_regions');

        $rows = $regionLib->getOneById($region_id);
        $region_path = $rows['region_path'];
        $region_path = explode(',',$region_path);

        $region_name=array();
        foreach($region_path as $k=>$v){
           if($v){
               $region = $regionLib->getOneById($v);
               $region_name[]=$region['local_name'];
           }
        }
        return implode('-',$region_name);
    }

    /**
    *检测地区是否已存在
    */
    function chkRegion($region_id,$area_id,&$msg){

        if($area_id)
        {
            $sqlstr.=' AND area_id!='.$area_id;
        }
        $region_id = explode(',',$region_id);
        foreach($region_id as $k=>$v){
            $sql = "SELECT * FROM sdb_logistics_area WHERE find_in_set(".$v.",region_id)".$sqlstr;

            $region=$this->db->select($sql);
            if($region){
                $region = kernel::single('eccommon_regions')->getOneById($region_id,'local_name');
                $msg=$region['local_name'];
                return false;
            }
        }
        return true;

    }
}
?>