<?php

class ome_mdl_branch extends dbeav_model {

    public static $branchList = null;
    public function _filter($filter, $tableAlias=null, $baseWhere=null) {
        $op_id = kernel::single('desktop_user')->get_id();
        if ($op_id) {//如果是系统同步，是没有当前管理员，默认拥有所有仓库权限
            $is_super = kernel::single('desktop_user')->is_super();
            if (!$is_super) {
                $branch_ids = $this->getBranchByUser(true);
                if ($branch_ids) {
                    if ($filter['branch_id'] && !is_array($filter['branch_id']) && in_array($filter['branch_id'], $branch_ids)) {
                        $filter['branch_id'] = $filter['branch_id'];
                    } elseif ($filter['branch_id'] && is_array($filter['branch_id'])) {
                        $realIds = array();
                        foreach($filter['branch_id'] as $id) {
                            if (in_array($id,  $branch_ids)) {
                                $realIds[] = $id;
                            }
                        }
                        if (!empty($realIds)) {
                            $filter['branch_id'] = $realIds;
                        } else {
                            $filter['branch_id'] = $branch_ids;
                        }
                    } else {
                        $filter['branch_id'] = $branch_ids;
                    }
                } else {
                    $filter['branch_id'] = 'false';
                }
            }
        }
        return parent::_filter($filter, $tableAlias, $baseWhere) . $where;
    }

    /*
     * 获取仓库对应的快递公司列表
     *
     * @param int $branch_id
     *
     * @return array
     */

    function get_corp($branch_id,$area='',$weight=0) {

        if (!$area || $area=='') {
            return $this->db->select("SELECT corp_id,name,type,weight,tmpl_type,channel_id FROM sdb_ome_dly_corp WHERE (branch_id=" . intval($branch_id) . " or all_branch='true') and disabled='false' ORDER BY weight DESC,branch_id DESC");   //代表该仓库找不到地区，默认为任意地方，所以任意物流公司都可送
        } else {
            //获取没有设置地区的物流公司，代表这类物流公司哪都送
            $corp1 = $this->db->select("SELECT corp_id,name,type,weight,tmpl_type,channel_id FROM sdb_ome_dly_corp WHERE (branch_id=" . intval($branch_id) . " or all_branch='true') and disabled='false' AND corp_id NOT IN(SELECT DISTINCT(corp_id) FROM sdb_ome_dly_corp_area) ORDER BY weight DESC,branch_id DESC");
            //获取物流公司和仓库有地区交叉的物流公司
            $oRegion = kernel::single('eccommon_regions');
            $rows = $this->db->select("SELECT corp_id,region_id FROM sdb_ome_dly_corp_area");
            $region_ids = array();
            foreach ($rows as $v) {
                $region_ids[$v['corp_id']][] = $v['region_id'];
            }

            $corp_region = array();
            $branch_region = explode(":", $area);
            $corp_region[] = $branch_region[2];
            $regionShip = $oRegion->getOneById($branch_region[2], "local_name,region_path");
            $region_path = explode(",", $regionShip['region_path']);
            array_shift($region_path);
            array_pop($region_path);
            array_pop($region_path);

            if ($region_path) {
                foreach ($region_path as $id) {
                    if (!in_array($id, $corp_region)) {
                        $corp_region[] = $id;
                    }
                }
            }

            $corp_list = array();
            foreach ($region_ids as $k => $v) {
                if (array_intersect($v, $corp_region)) {
                    $corp_list[] = $k;
                }else{
                    $corp_remove[] = $k;
                }
            }

            if (empty($corp_list)) {
                $corp_sys = $corp1;
            } else {
                $corp2 = $this->db->select("SELECT corp_id,name,type,weight,tmpl_type,channel_id FROM sdb_ome_dly_corp WHERE (branch_id=" . intval($branch_id) . " or all_branch='true') and disabled='false' AND corp_id IN(" . implode(",", $corp_list) . ") ORDER BY weight DESC,branch_id DESC");
                $corp_sys = array_merge($corp2,$corp1);
                $corp_sys = $this->sysSortArray($corp_sys,'weight',"SORT_DESC","SORT_NUMERIC");
            }
            foreach($corp_sys as $key=>$val){
                $corp_sys[$key]['name'] = $val['name'].'*';
            }

            if (empty($corp_remove)) {

                return $corp_sys;
            } else {
                $corp3 = $this->db->select("SELECT corp_id,name,type,weight,tmpl_type,channel_id FROM sdb_ome_dly_corp WHERE (branch_id=" . intval($branch_id) . " or all_branch='true') and disabled='false' AND corp_id IN(" . implode(",", $corp_remove) . ") ORDER BY weight DESC,branch_id DESC");
                $corp_sys = array_merge($corp_sys,$corp3);
                $corp_sys = $this->sysSortArray($corp_sys,'weight',"SORT_DESC","SORT_NUMERIC");


                return $corp_sys;
            }
        }
    }

    function save_branch($data) {
        $oBranch_area = &$this->app->model("branch_area");
        $areaGroupId = $data['areaGroupId'];
        $tmpGroupId = $oBranch_area->Getregion_id($areaGroupId);
        if ($data['branch_id'] != '') {
            $ret_region = $oBranch_area->Get_region($data['branch_id']);
            foreach ($ret_region as $k => $v) {
                if (in_array($v, $tmpGroupId) == false) {
                    $oBranch_area->Del_area($data['branch_id'], $v);
                }
            }
        }
        $this->save($data);
        foreach ($tmpGroupId as $k => $v) {
            $tmpdata = array(
                'branch_id' => $data['branch_id'],
                'region_id' => $v
            );
            $oBranch_area->save($tmpdata);
        }
    }

    function Get_name($branch_id) {
        $branch = $this->dump($branch_id, 'name');
        return $branch['name'];
    }

	function Getlist_name($branch_id) {
		if (!isset(self::$branchList[$branch_id])) {
			self::$branchList[$branch_id] = $this->Get_name($branch_id);
		}
		return self::$branchList[$branch_id];
	}

    /* 获取仓库列表 */

    function Get_branchlist() {
        $branch = $this->getList('branch_id,name,is_deliv_branch,online', '', 0, -1);

        return $branch;
    }

    /*
     * 获取仓库对应货号列表
     */

    function Get_poslist($branch_id) {

        $pos = $this->db->select('SELECT pos_id,store_position FROM sdb_ome_branch_pos WHERE branch_id=' . $branch_id);


        return $pos;
    }

    function fgetlist_csv(&$data, $filter, $offset) {
        $limit = 100;
        if ($filter['_gType']) {
            $title = array();
            if (!$data['title'])
                $data['title'] = array();
            $data['title']['' . $filter['_gType']] = '"' . implode('","', $this->io->data2local($this->io_title(array('type_id' => $filter['_gType'])))) . '"';
        }
        return false;
    }

    function io_title($filter, $ioType='csv') {
        $title = array();

        switch ($ioType) {
            case 'csv':
            default:

                $title = array(
                    $filter['type_id'],
                    'bn',
                    'name',
                    'store',
                    'sku_property',
                    'weight',
                    'store_position',
                );

                break;
        }
        $this->ioTitle['csv'][$filter['type_id']] = $title;
        return $title;
    }

    function export_csv($data) {
        $output = array();
        foreach ($data['title'] as $k => $val) {
            $output[] = $val . "\n" . implode("\n", (array) $data['content'][$k]);
        }
        echo implode("\n", $output);
    }

    /*
     * 获取操作员管辖仓库
     * getBranchByUser
     */

    function getBranchByUser($dataType=null) {
        $oBops = &$this->app->model('branch_ops');
        $Obranch = &$this->app->model('branch');

        $opInfo = kernel::single('ome_func')->getDesktopUser();
        $op_id = $opInfo['op_id'];

        $bops_list = $oBops->getList('branch_id', array('op_id' => $op_id), 0, -1);
        if ($bops_list)
            foreach ($bops_list as $k => $v) {
                $bps[] = $v['branch_id'];
            }
        if ($dataType)
            return $bps;
        if ($bps)
            $branch_list = $Obranch->getList('branch_id,name,uname,phone,mobile', array('branch_id' => $bps), 0, -1);
        if ($branch_list)
            ksort($branch_list);
        return $branch_list;
    }

    /*
     * 删除仓库：
     * 拒绝删除条件：关联货位
     */

    function pre_recycle($data=null) {
        $Obranch_product = &$this->app->model('branch_product');
        $Obranch_pos = &$this->app->model('branch_pos');
        $Obranch = &$this->app->model('branch');
        if (is_array($_POST['branch_id'])) {
            foreach ($_POST['branch_id'] as $key => $val) {
                #仓库与货位关联
                $Obranch_detail = $Obranch->dump($val, 'name');
                $pos = $Obranch_pos->dump(array('branch_id' => $val), 'pos_id');
                if (!empty($pos)) {
                    $this->recycle_msg = '仓库：' . $Obranch_detail['name'] . '已与货位建立关系,无法删除!';
                    return false;
                }
                #仓库与商品关联
                $Obranch_product_detail = $Obranch_product->dump(array('branch_id' => $val), 'product_id');
                if (!empty($Obranch_product_detail)) {
                    $this->recycle_msg = '仓库：' . $Obranch_detail['name'] . '已与商品建立关系,无法删除!';
                    return false;
                }
                $deled .= $Obranch_detail['name'] . " - ";
            }
            return true;
        }
    }

    function isExistOfflineBranch() {
        $row = $this->db->selectRow('select count(*) as total from  sdb_ome_branch where attr="false"');
        if ($row['total'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    function isExistOfflineBranchBywms($wms_id) {
        $wms_id = implode(',',$wms_id);
        $row = $this->db->selectRow('select count(*) as total from  sdb_ome_branch where attr="false" AND wms_id in ('.$wms_id.')');
        
        if ($row['total'] > 0) {
            return true;
        } else {
            return false;
        }
    }
    function isExistOnlineBranch() {
        $row = $this->db->selectRow('select count(*) as total from  sdb_ome_branch where attr="true"');
        if ($row['total'] > 0) {
            return true;
        } else {
            return false;
        }
    }
    function isExistOnlineBranchBywms($wms_id) {
        $wms_id = implode(',',$wms_id);
        $row = $this->db->selectRow('select count(*) as total from  sdb_ome_branch where attr="true" AND wms_id in ('.$wms_id.')');
        if ($row['total'] > 0) {
            return true;
        } else {
            return false;
        }
    }
    function getOnlineBranchs($field='*') {
        $rows = $this->db->select('select ' . $field . ' from sdb_ome_branch where attr="true" order by weight desc');

        return $rows;
    }
    function getOnlineBranchsBywms($field='*',$wms_id=array()) {
        $rows = $this->db->select('select ' . $field . ' from sdb_ome_branch where attr="true" AND wms_id in ('.implode(',',$wms_id).') order by weight desc');
        
        return $rows;
    }
    function getOfflineBranchs($field='*') {
        $rows = $this->db->select('select ' . $field . ' from sdb_ome_branch where attr="false" order by weight desc');

        return $rows;
    }
    function getOfflineBranchsBywms($field='*',$wms_id=array()) {
        $rows = $this->db->select('select ' . $field . ' from sdb_ome_branch where attr="false" AND wms_id in ('.implode(',',$wms_id).') order by weight desc');

        return $rows;
    }
    function getAllBranchs($field='*') {
        $rows = $this->db->select('select ' . $field . ' from sdb_ome_branch order by weight desc');

        return $rows;
    }

    /**
    * 对二维数组进行排序
    *
    * sysSortArray($Array,"Key1","SORT_ASC","SORT_RETULAR","Key2"……)
    * @param array   $ArrayData  需要排序的数组.
    * @param string $KeyName1    排序字段.
    * @param string $SortOrder1  顺序("SORT_ASC"|"SORT_DESC")
    * @param string $SortType1   排序类型("SORT_REGULAR"|"SORT_NUMERIC"|"SORT_STRING")
    * @return array              排序后的数组.
    */
    function sysSortArray($ArrayData,$KeyName1,$SortOrder1 = "SORT_ASC",$SortType1 = "SORT_REGULAR")
    {
        if(!is_array($ArrayData))
        {
              return $ArrayData;
        }
        // Get args number.
        $ArgCount = func_num_args();
        // Get keys to sort by and put them to SortRule array.
        for($I = 1;$I < $ArgCount;$I ++)
        {
              $Arg = func_get_arg($I);
              if(!eregi("SORT",$Arg))
              {
                  $KeyNameList[] = $Arg;
                  $SortRule[]    = '$'.$Arg;
              }
              else
              {
                  $SortRule[]    = $Arg;
              }
        }
        // Get the values according to the keys and put them to array.
        foreach($ArrayData AS $Key => $Info)
        {
              foreach($KeyNameList AS $KeyName)
              {
                  ${$KeyName}[$Key] = $Info[$KeyName];
              }
        }
        // Create the eval string and eval it.
        $EvalString = 'array_multisort('.join(",",$SortRule).',$ArrayData);';
        eval ($EvalString);
        return $ArrayData;
    }

    /* 获取发货仓绑定的备货仓信息 */
    function getDelivBranch($branch_id=0) {
        $filter = array('is_deliv_branch'=>'true');
        if($branch_id>0){
            $filter['branch_id'] = $branch_id;
        }
        $branchList = $this->getList('branch_id,name,is_deliv_branch,attr,bind_conf',$filter);
        foreach($branchList as $key=>$val){
            $val['bind_conf'] = unserialize($val['bind_conf']);
            $delivBranch[$val['branch_id']] = $val;
        }
        unset($branchList);

        return $delivBranch;
    }

    /***
    *
    */
    function get_corpbyarea($branch_id,$area='',$weight,$shop_type,$shop_id) {

        #说明:如果是店铺类型是亚马逊或当当的，则只能显示他自己的物流公司和通用的物流公司。并且非亚马逊或当当的店铺只能选择通用的物流公司。
        $sqlstr = '';
        $shop_data = array('DANGDANG','AMAZON');
        $shop_type = strtoupper($shop_type);
        if (!in_array($shop_type,$shop_data)) {  
            $shop_data = implode('\',\'',$shop_data );
            $sqlstr.=' AND `type` not in (\''.$shop_data.'\')';
        }else{                                 
            $tmp_shop_type = array($shop_type);
            $shop_diff = array_diff($shop_data,$tmp_shop_type);
            $shop_diff = implode('\',\'',$shop_diff );
            $sqlstr.=' AND `type` not in (\''.$shop_diff.'\')';
        }
        
        $sql = "SELECT corp_id,name,type,weight,shop_id,tmpl_type,channel_id FROM sdb_ome_dly_corp WHERE (branch_id=" . intval($branch_id) . " or all_branch='true') and disabled='false'".$sqlstr."  ORDER BY weight DESC,branch_id DESC";
        #获得哪都送的物流公司
        $corp1 = $this->db->select($sql);
        $copy_region = array();
        $corpId = kernel::single('logistics_rule')->autoMatchDlyCorp($area, $branch_id,$weight,$shop_type,$shop_id);
        $copy_region[] = $corpId;
        $corp_sys = $corp1;
        $branch_region = explode(":", $area);
        $corp_rule_list = array();
        foreach($corp_sys as $key=>$val){
            $corp_id = $val['corp_id'];
            $corp_rule_list[$corp_id] = $val;
            $corp_rule_list[$corp_id]['weight'] = $val['weight'];
            $flag='';
            $cost_freight = $this->app->model('delivery')->getDeliveryFreight($branch_region[2],$val['corp_id'],$weight);
            if($cost_freight==0){
                $cost_freight='运费未设置';
            }else{
                $cost_freight = '￥'.sprintf("%.2f",$cost_freight);
            }
            if(in_array($corp_id,$copy_region)){

                    $flag='(默认)';
                    $corp_rule_list[$val['corp_id']]['flag_select']=1;
            }
            $corp_str='';
            if($branch_rule['parent_id']!=0){
                if(in_array($corp_id,$copy_region)){
                    $corp_str='(复)';
                }
            }
            $electron='';
            if($val['tmpl_type']=='electron') {
                $electron='(电)';
            }
            $name = $val['name'].'：'.$cost_freight.$flag.$corp_str.$electron;
            $corp_rule_list[$val['corp_id']]['name'] = $name;
            $corp_rule_list[$val['corp_id']]['type'] = $val['type'];
            $corp_rule_list[$val['corp_id']]['shop_id'] = $val['shop_id'];
            $corp_rule_list[$val['corp_id']]['tmpl_type'] = $val['tmpl_type'];
            
        }

        sort($corp_rule_list);
        
        $corp_rule_list= $this->sysSortArray($corp_rule_list,'weight',"SORT_DESC","SORT_NUMERIC");

        return $corp_rule_list;
    }

	/*检查仓库名是否存在*/
	function namecheck($name)
	{
		$row = $this->getList('branch_id',array('name'=>$name));
		if($row) return $row[0]['branch_id'];
		else return false;
	}
}

?>