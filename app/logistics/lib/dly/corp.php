<?php
/**
* 转换物流公司设置表老数据
*
*/
class logistics_dly_corp{
    /**
     * 快递配置信息
     * @var $array
     */
    static $corpList = array();

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
    static $regionList = array();
    function turn_area_conf(){

        $oObj = &app::get('ome')->model('dly_corp');
        $db = kernel::database();
        $sql = 'SELECT area_fee_conf,corp_id,firstunit,continueunit FROM sdb_ome_dly_corp WHERE setting=1 ';
        $dly_corp = $db->select($sql);
        foreach ( $dly_corp as $k=>$v){

            $area_fee_conf = $v['area_fee_conf'] ? unserialize($v['area_fee_conf']) : "";
            foreach ($area_fee_conf as $ak=>$av){
                $area_fee_conf[$ak]['firstunit']    = $v['firstunit'];
                $area_fee_conf[$ak]['continueunit'] = $v['continueunit'];
            }
            $corp_id = $v['corp_id'];
            $corp_data = array();
            $corp_data['corp_id'] = $corp_id;
            $corp_data['area_fee_conf'] = serialize($area_fee_conf);

            $oObj->save($corp_data);
            foreach ($area_fee_conf as $key => $val) {
                if($val['areaGroupId']!=''){
                    $areas = $val['areaGroupId'];
                    $area_ids = explode(",", $areas);
                    if ($area_ids) {
                        $oObj->set_areaConf($area_ids, $v['corp_id'],$val);

                    }
                }
            }

        }
    }

    /**
    * 转换物流公司规则
    */
    function turn_dly_corplist(){

        $db = kernel::database();
        $rule_objModel = app::get('logistics')->model('rule_obj');
        $branch_ruleObj = app::get('logistics')->model('branch_rule');
        $branch = app::get('ome')->model('branch')->getlist('branch_id,name',array('is_deliv_branch'=>'true'),0,-1);
        #先跑指定地区物流设置规则
        echo '共'.count($branch).'个仓库需要转换<br><pre>';
        foreach($branch as $bk=>$bv){
            $branch_id=$bv['branch_id'];
            #查看此仓库是否存在
            $branch_rule = $branch_ruleObj->dump(array('branch_id'=>$branch_id),'branch_id');
            if(!$branch_rule){
                $branch_rule_data = array(
                    'branch_id'=>$branch_id,
                    'type'=>'custom',

                );
                $branch_ruleObj->save($branch_rule_data);

            }
            $rule = $this->set_region_rule($branch_id);

            #对排它规则进行排序

            foreach($rule as $rk=>$rv){
                if($rk!=''){
                    foreach ($rv as $rrk=>$rrv){

                       if($rrv['corp_id']=='-1'){
                           $rule[$rk][$rrk]['region_count']=0;
                       }else{
                            $rule[$rk][$rrk]['region_count']=count($rrv['region_id']);
                       }
                    }
                }else{
                    unset($rk);
                }
            }

            #对排它规则进行排序
            foreach($rule as $dk=>&$dv){

                usort($dv,array($this,'cmp'));
            }
           #取所有第一个数组作为主规则
            $default_rule = array();
            foreach ($rule as $rek=>$region_rule){
               $region_rule = array_shift($region_rule);
               $corp_id = $region_rule['corp_id'];
               $region_id = $rek;

               $default_rule[$corp_id]['region_id'][$rek]=$rek;
            }

            if($rule) {

                foreach($default_rule as $dk=>$v){
                    $region_id = $v['region_id']!='' ? implode(',',$v['region_id']):'';
                    if($dk=='-1') {
                        $dly_corp_name = '人工审单';
                    }else{
                        $dly_corp = app::get('ome')->model('dly_corp')->getlist('name',array('corp_id'=>$dk),0,1);
                        $dly_corp_name = $dly_corp[0]['name'];
                    }
                    $data = array();

                    $data['rule_name'] = $dly_corp_name.'默认规则';
                    $data['branch_id'] = $branch_id;
                    $data['default_corp_id'] = $dk;
                    $data['set_type'] = 'noweight';
                    $data['first_city'] = $this->get_regionname($region_id);
                    $data['p_region_id'] = $region_id;
                    $data['relationflag']=1;
                    $rule_exist = app::get('logistics')->model('rule')->dump(array('first_city_id'=>$region_id,'branch_id'=>$branch_id,'rule_name'=>$data['rule_name']),'rule_name');
                    if(!$rule_exist){
                        app::get('logistics')->model('rule')->createRule($data);
                    }
                }
                    #创建排它规则

                foreach($rule as $ok=>$ov){
                    foreach($ov as $ook=>$oov){
                        $corp_id = $oov['corp_id'];
                        $region_path=$ok;
                        $region_ruleobj = $rule_objModel->getlist('rule_id',array('branch_id'=>$branch_id,'region_id'=>$region_path));
                        $rule_id = $region_ruleobj[0]['rule_id'];
                        $rule_objSql = 'SELECT i.corp_id FROM  sdb_logistics_region_rule as r  LEFT JOIN sdb_logistics_rule_items as i on r.item_id=i.item_id LEFT JOIN sdb_logistics_rule_obj as o on o.obj_id=r.obj_id WHERE r.region_id='.$region_path.' AND o.branch_id='.$branch_id;

                        $rule_obj = $db->selectrow($rule_objSql);
                        if($rule_obj['corp_id']!=$corp_id){

                            $other_data = array();
                            $other_data['rule_id'] = $rule_id;
                            $other_data['default_corp_id'] = $corp_id;

                            foreach($oov['region_id'] as $region){
                                $other_data['area'][]=array('region_id'=>$region);
                            }
                            $other_data['branch_id'] = $branch_id;
                            $other_data['set_type'] = 'noweight';

                            $rule_objModel->create_rule_obj($other_data);
                        }
                   }


                }


                echo $bv['name'].'转换完成<br>';
            }else{
                echo $bv['name'].'没有需要转换的数据.....<br>';
            }
        }
        echo 'end';

    }

    /**
    * 获取区域对应物流公司
    * return Array

    */

    function set_region_rule($branch_id){
        $this->initCropData();
        $regionList = self::$region;

        $dly_corp_list = array();
        $confirmRoles = $this->fetchDefaultRoles();

        foreach ($regionList as $rk=>$region){

            $region_id = $rk;

            $corpId = $this->autoSelectDlyCorp($region_id,$branch_id,$confirmRoles);
            $region_path = $region;
            if($region_path){

                $region_path = explode(',',$region_path);
                $region_grade = count($region_path);
                $region_path=$region_path[1];
                if($region_path!=''){
                    if($corpId>0 || $corpId=='-1'){
                        if($region_grade=='5' || $region_grade=='4'){

                            $dly_corp_list[$region_path][$corpId]['region_id'][$rk] = $region_id;
                            $dly_corp_list[$region_path][$corpId]['corp_id'] = $corpId;

                        }
                    }
                }
            }
        }

        return $dly_corp_list;
    }



    /**
    * 获取区域名称
    */
    function get_regionname($region_id){
        $regions_ids = explode(',', $region_id);
        $region_arr = kernel::single('eccommon_regions')->getListByIds($regions_ids);

        foreach($region_arr as $k=>$v){
            if($v!=''){
                $region_name[]=$v['local_name'];
            }
        }
        $region_name = implode(',',$region_name);
        return $region_name;
    }

    /**
    *排它规则按照区域数排序
    */
    function cmp($a, $b)
    {
        if ($a['region_count'] == $b['region_count']) return 0;

        return ($a['region_count'] > $b['region_count']) ? -1 : 1;
    }

    /**
    * 通过区域获取物流公司ID
    */

    function autoSelectDlyCorp($shipArea, $branchId,$confirmRoles) {
        #通过区域匹配可送达的物流公司
        $this->initCropData();
        $regionPath = self::$region[$shipArea];
        $regionIds = explode(',', $regionPath);

        foreach($regionIds as $key=>$val){
            if($regionIds[$key] == '' || empty($regionIds[$key])){
                unset($regionIds[$key]);
            }
        }
        if(count($regionIds)<3 && count($regionIds)>0){
            foreach(self::$region as $key=>$val){
                if(strpos($val,$regionPath)!==false && $regionPath != $val){
                    $childIds[] = $key;
                }
            }
            if(count($childIds)>0){

                $dlyAreaObj = app::get('ome')->model('dly_corp_area');
                $dlyCount = $dlyAreaObj->count(array('region_id'=>$childIds));
                if($dlyCount>0){
                    return 0;
                }
            }
        }

        $corpIds = $this->getCorpByArea($regionPath, $branchId);

        #开启全境物流时获取全部可用物流公司
        if (empty($corpIds)) {
            if($confirmRoles == 0){
                $corpIds = $this->getDefaultCorp($branchId);

            }

        }
        #获取最佳物流
        if(!empty($corpIds)) {
            $corpId = $this->getBestCorpId($corpIds);
        }else{
            if($confirmRoles !=0){
                $corpId='-1';
            }
        }

        return $corpId;
    }

    /**
     * 通过发货地区的地区路径，获取可匹配的快递公司
     *
     * @param String $regionPath 发货地区的地区路径
     * @return Array;
     */
    private function getCorpByArea($regionPath, $branchId) {
        //$this->initCropData();
        $corpIds = array();
        //先查找有区域配置的快递公司
        if (!empty($regionPath)) {

            $regionIds = explode(',', $regionPath);
            if(count($regionIds)>1){
                array_shift($regionIds);
                array_pop($regionIds);
            }


            foreach ($regionIds as $rId) {

                foreach(self::$corpArea as $corpId => $cRegion) {

                    if (in_array($rId, $cRegion) && self::$corpList[$corpId]['setting']==0 && ((self::$corpList[$corpId]['branch_id'] == $branchId) || (self::$corpList[$corpId]['all_branch']=='true'))) {

                        $corpIds[$corpId] = true;
                    }
                }
            }
        }

        return $corpIds;
    }

    /**
     * 初始化快递公司配置
     *
     * @param void
     * @return void
     */
    private function initCropData() {

        if (!empty(self::$region)) {

            return;
        }

        $regionLib = kernel::single('eccommon_regions');
        //获取地区配置信息
        $regions = $regionLib->getList('region_id,region_path');
        foreach ($regions as $row) {

            self::$region[$row['region_id']] = $row['region_path'];
        }

        unset($regions);

        //获取地区配置信息
        $regionLib->getMap();
        #获取所有地区
        self::$regionList = $regionLib->regions;
        //获取快递公司配置信息
        $corp = app::get('ome')->model('dly_corp')->getList('branch_id, all_branch, corp_id, name, type, is_cod, weight,setting', array('disabled' => 'false'), 0, -1, 'weight DESC');

        foreach($corp as $item) {
            self::$corpList[$item['corp_id']] = $item;
        }
        unset($corp);

        //快递公司配送区域配置信息s
        $corpArea = app::get('ome')->model('dly_corp_area')->getList('*');

        foreach ($corpArea as $item) {

            self::$corpArea[$item['corp_id']][] = $item['region_id'];
        }

        unset($corpArea);
    }

    /**
     * 获取全局可用的物流
     *
     * @return Array
     */
    private function getDefaultCorp($branchId) {

        $corpIds = array();

        foreach (self::$corpList as $corpId => $info) {

            if (!isset(self::$corpArea[$corpId]) && ($branchId == $info['branch_id'] || $info['all_branch']=='true')) {
                $corpIds[$corpId] = true;
            }
        }

        return $corpIds;
    }
    /**
     * 获取最佳物流公司
     *
     * @param Array $corpIds 可用物流
     * @return Integer
     */
    private function getBestCorpId($corpIds) {

        //返回权重最高的
        $weight = -1;
        $id = 0;
        foreach ($corpIds as $corpId => $v) {

            if (self::$corpList[$corpId]['weight'] > $weight) {

                $weight = self::$corpList[$corpId]['weight'];
                $id = $corpId;
            }
        }

        return $id;
    }






    /**
     * 从数据库获取默认审单规则
     *
     * @param void
     * @return Array
     */
     function fetchDefaultRoles() {

        $configRow = app::get('omeauto')->model('autoconfirm')->getlist('config',array('disabled' => 'false'));
        $alldlycorp = 0;
        $noalldlycorp = 0;
        $alldly_corp_status = 0;
        foreach($configRow as $config){
            $config = $config['config'];
            if($config['allDlyCrop']==0){//启用
                $alldlycorp++;
            }
            if($config['allDlyCrop']==1){//不启用
                $noalldlycorp++;
            }

        }
        $all = count($configRow);
        if($all==$noalldlycorp){
            $alldly_corp_status = 1;
        }else if($all==$alldlycorp){
            $alldly_corp_status = 0;
        }else{//既启用又不启用
            $alldly_corp_status = 3;
        }
        return $alldly_corp_status;
    }
}

?>