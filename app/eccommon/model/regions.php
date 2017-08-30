<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class eccommon_mdl_regions extends dbeav_model{

    /**
     * 得到默认包的信息
     * @params null
     * @return object servicename
     */
    public function get_package_info()
    {
        return kernel::service('eccommon_regions_mainland');
    }

    /**
     * 得到地区名称
     * @params string regions id
     * @return array local_name的数组
     */
    public function getById($regionId='')
    {
        return $this->dump(intval($regionId), 'local_name');
    }

    /**
     * 得到指定id的地区信息
     * @params string region id
     * @return array 信息数组
     */
    public function getRegionByParentId($parentId)
    {
        return $this->dump(intval($parentId), 'region_id,local_name,p_region_id');
    }

    /**
     * 指定region id的下级信息
     * @params int region id
     * @return array - 所有地区数据数组
     */
    public function getAllChild($regionId)
    {
        $tmpRow = $this->dump(intval($regionId), 'region_path');
        $sql = "select region_id from ".$this->table_name(1)." where region_path like '%".$tmpRow['region_path']."%'";
        $row = $this->db->select($sql);

        if (is_array($row)&&count($row)>0)
        {
            foreach ($row as $key => $val)
            {
                $this->IdGroup[] = $val['region_id'];
            }
        }

        return $this->IdGroup;
    }

    /**
     * 得到指定region id同级的地区信息
     * @params int region id
     * @return array 地区信息
     */
    public function getGroupRegionId($regionId)
    {
       $row = $this->dump(intval($regionId), 'region_path');
       $path = $row['region_path'];
       $idGroup = array();

       $rows = $this->db->select($sql="select region_id from ".$this->table_name(1)." where region_path like '%".$path."%' and region_id<>".intval($regionId));
       if ($rows)
       {
           foreach ($rows as $key => $val)
           {
               $idGroup[] = $val['region_id'];
           }
       }

       return $idGroup;
    }

    /**
     * 得到指定region id的信息及父级的local_name
     * @params int region id
     * @return array
     */
    public function getDlAreaById($aRegionId)
    {
        $sql = 'select c.region_id,c.local_name,c.p_region_id,c.ordernum,p.local_name as parent_name from '.$this->table_name(1).' as c LEFT JOIN '.$this->table_name(1).' as p ON p.region_id=c.p_region_id where c.region_id='.intval($aRegionId);
        return $this->db->selectrow($sql);
    }

    /**
     * 取指定region id对应的region id
     * @params string name
     * @params int region id
     */
    public function checkDlArea($name,$p_region_id)
    {
        if ($p_region_id)
        {
            $aTemp = $this->dump(array('local_name' => $name, 'p_region_id' => $p_region_id), 'region_id');
        }
        else
        {
            $aTemp = $this->dump(array('local_name' => $name), 'region_id');
        }
        return $aTemp['region_id'];
    }

    function is_installed()
    {
        $row = $this->count();
        return $row;
    }

    /**
     * 清除指定包名下的地区信息
     * @params string 地区包名
     */
    public function clearOldData($package='')
    {
        if ($package)
            $sql="delete from ".$this->table_name(1)." where package='".$package."'";
        else
            $sql="delete from ".$this->table_name(1)." where 1";

        $this->db->exec($sql);
    }

}
