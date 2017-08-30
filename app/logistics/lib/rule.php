<?php
class logistics_rule {
    /**
     * 快递配置信息
     * @var $array
     */
    static $corpList = array();

    /**
     * 电子面单来源类型
     * @var $array
     */
    static $channelType = array();

    /**
     * 快递公司地区配置
     * @var Array
     */
    static $corpArea = array();

    /**
     * 地区配置信息
     * @var Array
     */
    static $region = array();

    //static $logiRule = array();

    /**
    *规则分组
    *@param obj_id
    *return array
    */
    function getGroupAreaRule($obj_id){
        $dly_corpObj = &app::get('ome')->model('dly_corp');
        $db = kernel::database();
        foreach($obj_id as $k=>$v){
            $sql = "SELECT min_weight,max_weight,corp_id FROM sdb_logistics_rule_items WHERE obj_id=$v ORDER BY min_weight,max_weight DESC";
            $rule_item =$db->select($sql);
            $rule_group_hash='';
            foreach($rule_item as $ik=>$iv){
                $rule_group_hash.=$iv['min_weight'].$iv['max_weight'].$iv['corp_id'];
            }
            $rule_group_hash=md5($rule_group_hash);

            $db->exec("UPDATE sdb_logistics_rule_obj SET rule_group_hash='$rule_group_hash' WHERE obj_id=$v");


        }
        $obj_ids = implode(',',$obj_id);
        $sql = "SELECT o.obj_id,o.rule_group_hash,o.set_type FROM sdb_logistics_rule_obj as o  where o.obj_id in($obj_ids) group by o.rule_group_hash";

        $rule_obj = $db->select($sql);

        $rule_list = array();
        foreach($rule_obj as $rk=>$rv){
            $rule_group_hash = $rv['rule_group_hash'];
            $obj_id = $rv['obj_id'];

            $region_list = $db->select("SELECT region_id,region_name,obj_id FROM sdb_logistics_rule_obj WHERE obj_id in ($obj_ids) AND rule_group_hash='$rule_group_hash'");

            $region_id=array();
            $region_name=array();
            $obj_id_list=array();
            foreach($region_list as $uk=>$uv){
                $region_id[]=$uv['region_id'];
                $region_name[]=$uv['region_name'];
                $obj_id_list[]=$uv['obj_id'];
            }

            $item_sql = "SELECT min_weight,max_weight,corp_id FROM sdb_logistics_rule_items WHERE obj_id=$obj_id ORDER BY min_weight,max_weight DESC";
            $rule_item =$db->select($item_sql);
            foreach($rule_item as $ik=>$iv){
                $dly_corp = $dly_corpObj->dump($iv['corp_id'],'name');
                $rule_item[$ik]['corp_name'] = $dly_corp['name'];
            }
            $rule_list[$rk]=array(
                'region_id'=>implode(',',$region_id),
                'region_name'=>implode(',',$region_name),
                'item_list'=>$rule_item,
                'set_type'=>$rv['set_type'],
                'obj_id'=>implode(',',$obj_id_list),
            );
        }

        return $rule_list;
    }



    /**
     * 根据收货地址匹配物流公司
     *
     * @param String $shipArea 送货地址
     * @return mixed
     */
    function autoMatchDlyCorp($shipArea, $branchId,$weight,$shop_type='',$shop_id='') {

        $this->initCropData($shop_type);
        $regionId = preg_replace('/.*:([0-9]+)$/is', '$1', $shipArea);
        $regionPath = self::$region[$regionId];

        $corpId = 0;
        if (!empty($regionPath)) {
            $regionIds = explode(',', $regionPath);
            foreach($regionIds as $key=>$val){
                if($regionIds[$key] == '' || empty($regionIds[$key])){
                    unset($regionIds[$key]);
                }
            }
            $corp = $this->getMapBranchRule($branchId,$regionIds,$weight);
            //增加判断如果取到指定物流公司，判断该物流公司是否支持该仓库发货
            if(self::$corpList[$corp['corp_id']] && (self::$corpList[$corp['corp_id']]['all_branch'] == 'true' || self::$corpList[$corp['corp_id']]['branch_id'] == $branchId)) {
                $channel_id = self::$corpList[$corp['corp_id']]['channel_id'];
                if(self::$corpList[$corp['corp_id']]['tmpl_type']=='electron' && self::$channelType[$channel_id]=='wlb') {
                    if(self::$corpList[$corp['corp_id']]['shop_id'] && self::$corpList[$corp['corp_id']]['shop_id']==$shop_id) {
                        $corpId = $corp['corp_id'];
                    } elseif($corp['second_corp_id']) {
                        $corpId = $corp['second_corp_id'];
                    }
                } else {
                    $corpId = $corp['corp_id'];
                }
            }
        }
        return $corpId;
    }




    /**
     * 初始化快递公司配置
     *
     * @param void
     * @return void
     */
    private function initCropData($shop_type) {
        if (!empty(self::$region)) {
            return;
        }
        /**/
        $corp_filter = array('disabled' => 'false');

        #说明:如果是店铺类型是亚马逊或当当的，则只能显示他自己的物流公司和通用的物流公司。并且非亚马逊或当当的店铺只能选择通用的物流公司。
        $shop_data = array('DANGDANG','AMAZON');
        $shop_type = strtoupper($shop_type);

        if (!in_array($shop_type,$shop_data)) {
            $corp_filter['type|notin']=$shop_data;
        }else{                                 
            $tmp_shop_type = array($shop_type);
            $shop_diff = array_diff($shop_data,$tmp_shop_type);
            $corp_filter['type|notin']=$shop_diff;
        }

        //获取快递公司配置信息
        $corp = app::get('ome')->model('dly_corp')->getList('branch_id, all_branch, corp_id, name, type, is_cod, weight, tmpl_type, channel_id, shop_id', $corp_filter, 0, -1, 'weight DESC');
         foreach($corp as $item) {
            self::$corpList[$item['corp_id']] = $item;
        }
        unset($corp);

        //获取地区配置信息
        $regions = kernel::single('eccommon_regions')->getList('region_id,region_path');
        foreach ($regions as $row) {
            self::$region[$row['region_id']] = $row['region_path'];
        }
        unset($regions);

        //电子面单来源类型
        $channelObj = &app::get("logisticsmanager")->model('channel');
        $channel = $channelObj->getList("channel_id,channel_type",array('status'=>'true'));
        foreach($channel as $val) {
            self::$channelType[$val['channel_id']] = $val['channel_type'];
            unset($val);
        }
        unset($channel);
    }





    /**
    * 获取仓库物流规则
    *
    */
     function getMapBranchRule($branch_id,$regionIds,$weight){
        $branch_rule = kernel::database()->select('SELECT branch_id,type,parent_id FROM sdb_logistics_branch_rule WHERE branch_id='.$branch_id.' ORDER BY branch_id DESC');
        $rule_map = array();
        $branch_rule = $branch_rule[0];
        $branch_id= $branch_rule['branch_id'];
        $sqlstr='';
        if($branch_rule['type']=='other' && $branch_rule['parent_id']!=0){
            $parent_id=0;
            app::get('logistics')->model('branch_rule')->getBranchRuleParentId($branch_id,$parent_id);
            $sqlstr.=' WHERE o.branch_id='.$parent_id;

        }else{
            $sqlstr.=' WHERE o.branch_id='.$branch_id;

        }
        $sql = 'SELECT o.set_type,o.rule_type,r.item_id,r.region_id,i.* FROM sdb_logistics_rule_obj as o LEFT JOIN sdb_logistics_region_rule as r ON o.obj_id=r.obj_id left join sdb_logistics_rule_items as i on r.item_id=i.item_id  '.$sqlstr.' AND o.region_id in ('.implode(',',$regionIds).') group by i.item_id ORDER BY i.item_id DESC';
        
        $region_rule = kernel::database()->select($sql);
        
        $corp_rule = array();
        foreach ($region_rule as $rk=>$rule) {
            $corp_rule[$rule['rule_type']][$rule['region_id']][$rk]=array(
                'corp_id' =>$rule['corp_id'],
                'second_corp_id' =>$rule['second_corp_id'],
                'min_weight'=>$rule['min_weight'],
                'max_weight'=>$rule['max_weight'],
                'set_type'=>$rule['set_type']
            );
        }

        $corp = array();
        
        if ($corp_rule['default']) {
            if($corp_rule['other']) {
                $corp = $this->getCorpByArea($corp_rule['other'],$regionIds,$weight);//先匹配二级下属地区规则
                if(!$corp || ($corp['corp_id']<0 && $corp['corp_id']!='-1')) {
                    $corp = $this->getCorpByArea($corp_rule['default'],$regionIds,$weight);//再匹配一级地区
                }
            } else {
                $corp = $this->getCorpByArea($corp_rule['default'],$regionIds,$weight);
            }
        }

        return $corp;
    }

    /**
    * 获取区域对应物流公司
    *
    */
     function getCorpByArea($corp_rule,$regionIds,$weight) {
        $regionIds = array_reverse($regionIds);

        foreach($regionIds as $rId){

            if (isset($corp_rule[$rId])) {

                foreach ($corp_rule[$rId] as $rk=>$rv) {
                    
                    if ($rv['set_type'] =='noweight') {
                        $corp = $rv;
                        break 2;
                    } else {
                        if($weight>=$rv['min_weight'] && $weight<$rv['max_weight']){
                            $corp = $rv;
                            break 2;
                        }else if($weight>=$rv['min_weight'] && $rv['max_weight']=='-1'){
                            $corp = $rv;
                            break 2;
                        }
                    }

                }
            }

        }
        return $corp;

}

}
