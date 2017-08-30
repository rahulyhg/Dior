<?php

class ome_ctl_admin_dly_corp extends desktop_controller {

    var $name = "物流公司管理";
    var $workground = "goods_manager";

    function _views() {

        $mdl_dly_corp = app::get('ome')->model('dly_corp');
        $branch_ids = $this->_getBranchIds();
        $branch_list = app::get('ome')->model('branch')->getList('*', array('disabled' => 'false', 'online'=> 'true', 'branch_id'=>$branch_ids));

        $sub_menu = array();
        $sub_menu[0] = array();
        $ids = array(0);
        foreach ($branch_list as $branch) {
            $sub_menu[$branch['branch_id']] = array('label' => $branch['name'], 'filter' => array('branch_id' => $branch['branch_id']), 'optional' => false);
            $ids[] = $branch['branch_id'];
        }

        if (empty($ids)) {
            $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => array(), 'optional' => false);
        } else {
            $sub_menu[0] = array('label' => app::get('base')->_('全部'), 'filter' => array('branch_id' => $ids), 'optional' => false);
        }

        $sub_menu[999] = array('label' => app::get('base')->_('全部仓库'), 'filter' => array('all_branch'=>'true','branch_id'=>0), 'optional' => false);

        foreach ($sub_menu as $k => $v) {
            $sub_menu[$k]['filter'] = $v['filter'] ? $v['filter'] : null;
            $sub_menu[$k]['addon'] = $mdl_dly_corp->count($v['filter']);
            $sub_menu[$k]['href'] = 'index.php?app=ome&ctl=admin_dly_corp&act=index&view=' . $k;
        }
        return $sub_menu;
    }

    function _getBranchIds() {

        $op_id = kernel::single('desktop_user')->get_id();
        if ($op_id) {//如果是系统同步，是没有当前管理员，默认拥有所有仓库权限
            $is_super = kernel::single('desktop_user')->is_super();
            if (!$is_super) {
                $branch_ids = app::get('ome')->model('branch')->getBranchByUser(true);
                if ($branch_ids) {
                    return $branch_ids;
                } else {
                    return array();
                }
            } else {
                $ret = array();
                $branch_ids = app::get('ome')->model('branch')->getList('branch_id', array('disabled' => 'false', 'online'=> 'true'));
                foreach($branch_ids as $row) {
                    $ret[] = $row['branch_id'];
                }

                return $ret;
            }
        }
    }

    function index() {

        $_GET['view'] = intval($_GET['view']);

        if (!empty($_GET['view']) && $_GET['view']!=0) {
            $filter = array('branch_id' => $_GET['view']);
        } else {
            $branch_ids = $this->_getBranchIds();
            $branch_ids = array_merge(array(0),$branch_ids);
            $filter = array('branch_id'=>$branch_ids);
        }

        $this->finder('ome_mdl_dly_corp', array(
            'title' => '物流公司管理',
            'filter' => $filter,
            'actions' => array(
                array('label' => '添加物流公司', 'href' => 'index.php?app=ome&ctl=admin_dly_corp&act=add&finder_id=' . $_GET['finder_id'], 'target' => '_blank'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
        ));
    }

    function add(){
        #获取当前仓库模式
        $branch_mode = $this->app->getConf('ome.branch.mode');

        $this->pagedata['config'] = array(
            'firstunit' => '1000',
            'continueunit' => '1000'
        );
        if (app::get('logisticsmanager')->is_installed()) {
            //新版控件打印
            $templateObj = &app::get("logisticsmanager")->model('express_template');
            $templates = $templateObj->getList("template_id,template_name,template_type");
            foreach($templates as $val){
                if ($val['template_type']=='normal') {
                    $normalTmpl[] = $val;
                } else {
                    $electronTmpl[] = $val;
                }
                unset($val);
            }
            $this->pagedata['normalTmpl'] = $normalTmpl;
            $this->pagedata['electronTmpl'] = $electronTmpl;

            //获取面单渠道
            $channelShop = $channelType = $shopList = array();
            $shopObj = &app::get("ome")->model('shop');
            $shop = $shopObj->getList("shop_id,name");
            foreach($shop as $val) {
                $shopList[$val['shop_id']] = $val['name'];
                unset($val);
            }
            $channelObj = &app::get("logisticsmanager")->model('channel');
            $channel = $channelObj->getList("*",array('status'=>'true'));
            foreach($channel as $val) {
                $channelType[$val['channel_id']] = $val['channel_type'];
                $channelShop[$val['channel_id']]['shop_id'] = $val['shop_id'];
                $channelShop[$val['channel_id']]['name'] = $shopList[$val['shop_id']];
                //订单公司编码
                if ($val['channel_type'] == 'sf') {
                    $channelShop[$val['channel_id']]['logistics_code'] = 'SF';
                }
                elseif ($val['channel_type'] == 'yunda') {
                    $channelShop[$val['channel_id']]['logistics_code'] = 'YUNDA';
                }
                elseif ($val['channel_type'] == '360buy') {
                    //$val['logistics_code'] == 'SOP'
                    $channelShop[$val['channel_id']]['logistics_code'] = 'JDCOD';
                    $channelType[$val['channel_id']] = 'JDCOD';
                }elseif ($val['channel_type'] == 'taobao') {
                    $channelType[$val['channel_id']] = $val['logistics_code'];
                }
                else {
                    $channelShop[$val['channel_id']]['logistics_code'] = $val['logistics_code'];
                }
                
                unset($val);
            }
            $this->pagedata['channel'] = $channel;
            $this->pagedata['channelShop'] = json_encode($channelShop);
            $this->pagedata['channelType'] = json_encode($channelType);

            $this->pagedata['logisticsmanager'] = 'true';
            $deploy = base_setup_config::deploy_info();
            $version = $deploy['product_name'];
            preg_match('/第三方仓储版/',$version,$filtcontent);
            if($filtcontent){
                $this->pagedata['logisticsmanager'] = 'false';
            }
        } else {
            //老版falsh打印
            $templateObj = &$this->app->model('print_tmpl');
            $this->pagedata['prttpl'] = $templateObj->getList("prt_tmpl_id,prt_tmpl_title");
            $this->pagedata['logisticsmanager'] = 'false';
        }
        $this->pagedata['branchList'] = app::get('ome')->model('branch')->getList('branch_id,name', array('disabled' => 'false', 'online'=> 'true'));
        $this->pagedata['weightunit'] = $this->_weightunit();
        $this->pagedata['branch_mode'] = $branch_mode;
        unset($branch_mode);
        $this->pagedata['title'] = '添加物流公司';
        $this->singlepage("admin/system/dly_corp.html");
    }

    function editdly_corp($corp_id) {
        #获取当前仓库模式
        $branch_mode = $this->app->getConf('ome.branch.mode');
        $this->pagedata['branch_mode'] = $branch_mode;
        unset($branch_mode);
        $oDlycorp = &$this->app->model('dly_corp');
        $dly_info = $oDlycorp->dump($corp_id);
        $dly_info['area_fee_conf'] = unserialize($dly_info['area_fee_conf']);

        //new add start
        foreach($dly_info['area_fee_conf'] as $key=>$val){
            if($val['areaGroupBakId'] && $val['areaGroupBakId'] != ''){
                $dly_info['area_fee_conf'][$key]['areaGroupId'] = $val['areaGroupBakId'];
            }
        }
        //new add end

        $dly_info['dlytype_readonly'] = 1;

        $oDly_corp = &app::get('ome')->model('dly_corp');
        $corp_list = $oDly_corp->corp_default();
        unset($corp_list['OTHER']);
        $corp_lists = array_keys($corp_list);

        if(!in_array($dly_info['type'],$corp_lists)){
             $dly_info['dlytype_readonly'] = 0;
        }
        $dly_info['protect_rate'] = $dly_info['protect_rate'] * 100;
        if (app::get('logisticsmanager')->is_installed()) {
            //新版控件打印
            $templateObj = &app::get("logisticsmanager")->model('express_template');
            $templates = $templateObj->getList("template_id,template_name,template_type");
            foreach($templates as $val){
                if ($val['template_type']=='normal') {
                    $normalTmpl[] = $val;
                } else {
                    $electronTmpl[] = $val;
                }
            }
            $this->pagedata['normalTmpl'] = $normalTmpl;
            $this->pagedata['electronTmpl'] = $electronTmpl;

            //获取面单渠道
            $channelShop = $channelType = $shopList = array();
            $shopObj = &app::get("ome")->model('shop');
            $shop = $shopObj->getList("shop_id,name");
            foreach($shop as $val) {
                $shopList[$val['shop_id']] = $val['name'];
                unset($val);
            }
            $channelObj = &app::get("logisticsmanager")->model('channel');
            $channel = $channelObj->getList("*",array('status'=>'true'));
            foreach($channel as $val) {
                $channelShop[$val['channel_id']]['shop_id'] = $val['shop_id'];
                $channelShop[$val['channel_id']]['name'] = $shopList[$val['shop_id']];
                $channelShop[$val['channel_id']]['logistics_code'] = $val['logistics_code'];
                if ($val['channel_type']=='taobao') {
                   $channelType[$val['channel_id']] = $val['logistics_code']; 
                }else if($val['channel_type'] == '360buy'){
                    $channelShop[$val['channel_id']] = 'JDCOD';
                    $channelType[$val['channel_id']] = 'JDCOD';
                } 
                else{
                    $channelType[$val['channel_id']] = $val['channel_type'];
                }
                
                unset($val);
            }
            if ($channelType[$dly_info['channel_id']]=='wlb') {
                $dly_info['shop_name'] = $shopList[$dly_info['shop_id']];
            } else {
                $dly_info['shop_name'] = '全部';
            }

            $this->pagedata['channel'] = $channel;
            $this->pagedata['channelShop'] = json_encode($channelShop);
            $this->pagedata['channelType'] = json_encode($channelType);

            $this->pagedata['logisticsmanager'] = 'true';
            $deploy = base_setup_config::deploy_info();
            $version = $deploy['product_name'];
            preg_match('/第三方仓储版/',$version,$filtcontent);
            if($filtcontent){
                $this->pagedata['logisticsmanager'] = 'false';
            }
        } else {
            //老版falsh打印
            $templateObj = &$this->app->model('print_tmpl');
            $this->pagedata['prttpl'] = $templateObj->getList("prt_tmpl_id,prt_tmpl_title");
            $this->pagedata['logisticsmanager'] = 'false';
        }
        $this->pagedata['dt_info'] = $dly_info;
        $this->pagedata['weightunit'] = $this->_weightunit();
        $this->pagedata['branchList'] = app::get('ome')->model('branch')->getList('branch_id,name', array('disabled' => 'false', 'online'=> 'true'));
        $this->pagedata['prttmpid'] = $dly_info['prt_tmpl_id'];
        $this->pagedata['title'] = '编辑物流公司';
        $this->singlepage("admin/system/dly_corp.html");
    }

    function saveDlType() {
        $this->begin('index.php?app=ome&ctl=admin_dly_corp');
        $oObj = &$this->app->model('dly_corp');
        $dlycorp = $_POST;
        #获取当前仓库模式
        $branch_mode = $this->app->getConf('ome.branch.mode');
        if($branch_mode!=$dlycorp['branch_mode']){
            $this->end(false, app::get('base')->_('仓库模式异常,请重新刷新后再操作！'));
        }
        if($dlycorp['all_branch']=='true'){
            $dlycorp['branch_id'] = 0;
        }

        if ($dlycorp['firstprice'] < 0 || $dlycorp['continueprice'] < 0) {
            $this->end(false, app::get('base')->_('首重费用和续重费用都不能为负！'));
        }

        if (app::get('logisticsmanager')->is_installed()) {
            $dlycorp['prt_tmpl_id'] = ($dlycorp['tmpl_type']=='normal') ? $dlycorp['normal_tmpl_id'] : $dlycorp['electron_tmpl_id'];
            unset($dlycorp['normal_tmpl_id'],$dlycorp['electron_tmpl_id']);
            if($dlycorp['tmpl_type']=='normal') {
                $dlycorp['shop_id'] = '';
                $dlycorp['channel_id'] = 0;
            }
        }

        //配送地区
        $area_fee_conf = array();
        if ($dlycorp['area_fee_conf']['areaGroupName'])
            foreach ($dlycorp['area_fee_conf']['areaGroupName'] as $k => $v) {
                $ishave = 0;
                $c['areaGroupName'] = $v;
                $c['areaGroupId'] = $dlycorp['area_fee_conf']['areaGroupId'][$k];
                $c['firstprice'] = $dlycorp['area_fee_conf']['firstprice'][$k];
                $c['continueprice'] = $dlycorp['area_fee_conf']['continueprice'][$k];
                $c['dt_expressions'] = $dlycorp['area_fee_conf']['dt_expressions'][$k];
                $c['dt_useexp'] = $dlycorp['area_fee_conf']['dt_useexp'][$k];
                #增加首重续重
                $c['firstunit'] = $dlycorp['area_fee_conf']['firstunit'][$k];
                $c['continueunit'] = $dlycorp['area_fee_conf']['continueunit'][$k];
                if($c['firstunit'] <= 0 || $c['continueunit'] <= 0){
                    $this->end(false, app::get('base')->_('首重和续重必须设置并大于0'));
                }
                #
                if ($c['firstprice'] < 0 || $c['continueprice'] < 0) {
                    $this->end(false, app::get('base')->_('配送地区的首重费用和续重费用都不能为负！'));
                }

                if (!$c['areaGroupName'] and !$c['areaGroupId'] and !$c['firstprice'] and !$c['continueprice'] and !$c['dt_expressions'] and !$c['dt_useexp'])
                    $ishave = 0;
                else
                    $ishave = 1;
                $area_fee_conf[] = $c;
            }
//        if ($dlycorp['firstunit'] <= 0 || $dlycorp['continueunit'] <= 0) {
//            $this->end(false, app::get('base')->_('首重和续重必须设置并大于0'));
//        }
        $dlycorp['area_fee_conf'] = $area_fee_conf;
        $dlycorp['protect'] = $dlycorp['protect'] ? $dlycorp['protect'] : 'false';

        //new add start
        if(is_array($dlycorp['area_fee_conf']) && count($dlycorp['area_fee_conf'])>0){
            $dlycorp = $this->getAreaConf($dlycorp);
        }
        //new add end

        $oObj->save($dlycorp);

        $dlycorp_area_fee_conf = $dlycorp['area_fee_conf'] ? unserialize($dlycorp['area_fee_conf']) : "";

        #添加至明细表里
        $areaObj = &$this->app->model('dly_corp_area');
        $corp_itemsObj = &$this->app->model('dly_corp_items');
        $corp_area_result = $areaObj->delete(array('corp_id' => $dlycorp['corp_id']));
        if($corp_area_result){
            $corp_itemsObj->delete(array('corp_id' => $dlycorp['corp_id']));
        }
        if ($dlycorp_area_fee_conf)
            foreach ($dlycorp_area_fee_conf as $key => $val) {
                $areas = $val['areaGroupId'];
                $area_ids = explode(",", $areas);
                if ($area_ids) {
                    $oObj->set_area($area_ids, $dlycorp['corp_id']);
                    #重量区间价格等设置
                    $oObj->set_areaConf($area_ids,$dlycorp['corp_id'],$val);
                }
            }

        $this->end(true, app::get('base')->_('保存成功'));
    }

    function getAreaConf($dlycorp){
        $regionLib = kernel::single('eccommon_regions');
        $regions = $regionLib->getList('region_id,local_name,p_region_id,region_path,region_grade');

        /* 生成完整地区的树 start */
        if(is_array($regions) && count($regions)>0){
            foreach($regions as $key=>$val){
                $regionData[$val['region_id']] = $val;
                if($val['region_grade']==1){
                    $regionMap[$val['region_id']]['region_id'] = $val['region_id'];
                    $regionMap[$val['region_id']]['local_name'] = $val['local_name'];
                }elseif($val['region_grade']==2){
                    $regionMap[$val['p_region_id']]['childId'][$val['region_id']] = $val['region_id'];
                }elseif($val['region_grade']==3){
                    $region_path = explode(',', $val['region_path']);
                    $regionMap[$region_path[1]]['child'][$region_path[2]]['childId'][$val['region_id']] = $val['region_id'];
                }
            }
        }

        if(is_array($regionMap) && count($regionMap)>0){
            foreach($regionMap as $key=>$val){
                if($val['childId'] && is_array($val['childId'])){
                    ksort($regionMap[$key]['childId']);
                    $regionMap[$key]['md5str'] = md5(implode(',',$regionMap[$key]['childId']));
                }
                foreach((array)$val['child'] as $k=>$v){
                    if($v['childId'] && is_array($v['childId'])){
                        ksort($regionMap[$key]['child'][$k]['childId']);
                        $regionMap[$key]['child'][$k]['md5str'] = md5(implode(',',$regionMap[$key]['child'][$k]['childId']));
                    }
                }
            }
        }
        /* 生成完整地区的树 end */

        if(is_array($dlycorp['area_fee_conf']) && count($dlycorp['area_fee_conf'])>0){
            foreach($dlycorp['area_fee_conf'] as $key=>$val){
                $areaGroup=explode(",",$val['areaGroupId']);

                /* 去除没有父地区ID的地区 start */
                foreach($areaGroup as $k=>$v){
                    if(strpos($v,"|") !== false){
                        $tmp = array();
                        $tmp = explode("|",$v);
                        $areaGroup[$k] = $tmp[0];
                    }
                }
                foreach((array)$areaGroup as $areaId){
                    if($regionData[$areaId]){
                        if($regionData[$areaId]['region_grade']==1){
                            $allAreaMap[$regionData[$areaId]['region_id']]['region_id'] = $regionData[$areaId]['region_id'];
                        }elseif($regionData[$areaId]['region_grade']==2){
                            $allAreaMap[$regionData[$areaId]['p_region_id']]['child'][$regionData[$areaId]['region_id']]['region_id'] = $regionData[$areaId]['region_id'];
                        }elseif($regionData[$areaId]['region_grade']==3){
                            $region_path = explode(',', $regionData[$areaId]['region_path']);
                            $allAreaMap[$region_path[1]]['child'][$region_path[2]]['child'][$regionData[$areaId]['region_id']] = $regionData[$areaId]['region_id'];
                        }
                    }
                }
                foreach((array)$allAreaMap as $areaVal){
                    if($areaVal['region_id'] && $areaVal['region_id']>0){
                        $newAreaGroup[] = $areaVal['region_id'];
                        foreach((array)$areaVal['child'] as $childVal){
                            if($childVal['region_id'] && $childVal['region_id']>0){
                                $newAreaGroup[] = $childVal['region_id'];
                                foreach((array)$childVal['child'] as $ccVal){
                                    $newAreaGroup[] = $ccVal;
                                }
                            }
                        }
                    }
                }
                /* 去除没有父地区ID的地区 end */

                //$dlycorp['area_fee_conf'][$key]['areaGroupId'] = implode(',',$newAreaGroup); // 保存修正后的地区
                $newAreaGroup = kernel::single('ome_region')->get_region_node($newAreaGroup); // 如果有子地区，去除父地区

                /* 生成配置地区的树 start */
                foreach((array)$newAreaGroup as $area){
                    if($regionData[$area]){
                        if($regionData[$area]['region_grade']==1){
                            $areaMap[$regionData[$area]['region_id']]['region_id'] = $regionData[$area]['region_id'];
                        }elseif($regionData[$area]['region_grade']==2){
                            $areaMap[$regionData[$area]['p_region_id']]['childId'][$regionData[$area]['region_id']] = $regionData[$area]['region_id'];
                        }elseif($regionData[$area]['region_grade']==3){
                            $region_path = explode(',', $regionData[$area]['region_path']);
                            $areaMap[$region_path[1]]['child'][$region_path[2]]['childId'][$regionData[$area]['region_id']] = $regionData[$area]['region_id'];
                        }
                    }
                }
                foreach((array)$areaMap as $akey=>$aval){
                    if($aval['childId'] && is_array($aval['childId'])){
                        ksort($areaMap[$akey]['childId']);
                        $areaMap[$akey]['md5str'] = md5(implode(',',$areaMap[$akey]['childId']));
                    }
                    foreach((array)$aval['child'] as $k=>$v){
                        if($v['childId'] && is_array($v['childId'])){
                            ksort($areaMap[$akey]['child'][$k]['childId']);
                            $areaMap[$akey]['child'][$k]['md5str'] = md5(implode(',',$areaMap[$akey]['child'][$k]['childId']));
                            if($areaMap[$akey]['child'][$k]['md5str'] == $regionMap[$akey]['child'][$k]['md5str']){
                                $areaMap[$akey]['childId'][$k] = $k;
                                ksort($areaMap[$akey]['childId']);
                                $areaMap[$akey]['md5str'] = md5(implode(',',$areaMap[$akey]['childId']));
                            }
                        }
                    }
                }
                /* 生成配置地区的树 end */
                $areaMapData[$key] = $areaMap;
                unset($areaGroup,$allAreaMap,$newAreaGroup,$areaMap);
            }
        }

        /* 配置地区树与完整地区树作比较 start */
        if(is_array($areaMapData) && count($areaMapData)>0){
            foreach($areaMapData as $key=>$val){
                foreach((array)$val as $k=>$v){
                    if(($v['md5str'] && $v['md5str'] == $regionMap[$k]['md5str']) || (!$v['childId'] && !$v['child'] && $v['region_id']>0)){
                        $dlycorp['area_fee_conf'][$key]['newId'][] = $k;
                    }else{
                        if($v['childId'] && is_array($v['childId'])){
                            foreach((array)$v['childId'] as $childk=>$childv){
                                $dlycorp['area_fee_conf'][$key]['newId'][] = $childv;
                            }
                        }
                        if($v['child'] && is_array($v['child'])){
                            foreach((array)$v['child'] as $ck=>$cv){
                                if($cv['md5str'] && $cv['md5str'] == $regionMap[$k]['child'][$ck]['md5str']){
                                    $dlycorp['area_fee_conf'][$key]['newId'][] = $ck;
                                }else{
                                    if($cv['childId'] && is_array($cv['childId'])){
                                        foreach((array)$cv['childId'] as $cck=>$ccv){
                                            $dlycorp['area_fee_conf'][$key]['newId'][] = $ccv;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        /* 配置地区树与完整地区树作比较 end */

        if(is_array($dlycorp['area_fee_conf']) && count($dlycorp['area_fee_conf'])>0){
            foreach($dlycorp['area_fee_conf'] as $key=>$val){
                $dlycorp['area_fee_conf'][$key]['areaGroupBakId'] = $val['areaGroupId'];

                $dlycorp['area_fee_conf'][$key]['areaGroupId'] = implode(',',$val['newId']);
                unset($dlycorp['area_fee_conf'][$key]['newId']);
            }
        }
        return $dlycorp;
    }

    function _weightunit() {
        return array(
            "500" => app::get('base')->_("500克"),
            "1000" => app::get('base')->_("1公斤"),
            "1200" => app::get('base')->_("1.2公斤"),
            "2000" => app::get('base')->_("2公斤"),
            "5000" => app::get('base')->_("5公斤"),
            "10000" => app::get('base')->_("10公斤"),
            "20000" => app::get('base')->_("20公斤"),
            "50000" => app::get('base')->_("50公斤")
        );
    }

    function showRegionTreeList($serid, $multi=false) {
        if ($serid) {
            $this->pagedata['sid'] = $serid;
        } else {
            $this->pagedata['sid'] = substr(time(), 6, 4);
        }
        $this->pagedata['multi'] = $multi;
        $this->display('admin/system/regionSelect.html');
    }

    function getRegionById($pregionid) {
        $oDlyType = &$this->app->model('dly_corp');
        echo json_encode($oDlyType->getRegionById($pregionid));
    }

    function checkExp() {
        $this->pagedata['expressions'] = $_GET['expvalue'];
        $this->display('admin/delivery/check_exp.html');
    }

    function corp_recommend() {
        $oDly_corp = &app::get('ome')->model('dly_corp');
        $this->pagedata['corp_list'] = $oDly_corp->corp_default();
        $this->display('admin/system/dly_corp_recommend.html');
    }

    function select_dly_status($logistics_code,$tracking_no){
        $rpc_dly = kernel::single('ome_delivery_logistics');
        $oDly_corp = &app::get('ome')->model('dly_corp');
        $row = $oDly_corp->getList('*',array('type'=>$logistics_code));
        $all_dly_corp = $oDly_corp->corp_default();
        $kdapi_code  = $all_dly_corp[$row[0]['type']]['kdapi_code'];
        $request_url  = $row[0]['request_url'];
        $in_verifycode = array('ems','shentong','shunfeng');
        if($rpc_dly->get_verifycode($kdapi_code)){
            $this->pagedata['verifycode'] = 'true';
            $this->pagedata['time'] = time();
            $this->pagedata['kdapi_code'] = $kdapi_code;
            $this->pagedata['tracking_no'] = $tracking_no;
        }else{
            $rpc_data['logistics_code'] = $kdapi_code;
            $rpc_data['tracking_no'] = $tracking_no;
            $data = $rpc_dly->rpc_logistics_info($rpc_data);
            if($data['status'] === 1){
                $this->pagedata['return_data'] = $data['data'];
            }else{
                if($data['message']) $this->pagedata['message'] = $data['message'];
            }
        }
        $this->pagedata['desktop_res'] = app::get('desktop')->res_url;
        $this->pagedata['request_url'] = $request_url;
        $this->display('admin/system/select_dly_status.html');
    }

    function get_verifycode($logistics_code){
        kernel::single('base_session')->start();
        header("Content-type: image/png");
        if($_SESSION['dly_verifycode']){
            echo $_SESSION['dly_verifycode'];
            unset($_SESSION['dly_verifycode']);
            exit;
        }else{
            $rpc_dly = kernel::single('ome_delivery_logistics');
            echo $rpc_dly->get_verifycode($logistics_code);
            unset($_SESSION['dly_verifycode']);
            exit;
        }
    }

    
    /**
     * 查询物流到不到
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function get_arrived()
    {
        $corpObj = app::get('ome')->model('dly_corp');
        $arrivedObj = kernel::single('omeauto_auto_plugin_arrived');
        $checkCorp = $arrivedObj->getCheckCorp();
        $corp_id = $_POST['corp_id'];
        $corp_detail = $corpObj->dump($corp_id,'type');
        if (!in_array($corp_detail['type'],$checkCorp)) {
            $result = 5;
            echo $result;
        }else{
            $corp = $corp_detail['type'];
            $address = $_POST['addr'];
            $area =  $_POST['area'];
            $areas =explode(':', $area);
            $area = $areas[1];
            $area = preg_replace("/省|市|县|区/","",$area);

            
            $arrivedObj->address = $areas[1].$address;
            $arrivedObj->area =$area;
            $arrivedObj->corp = $corp;
            
            $result = $arrivedObj->request();
            echo $result;
        }
        
    }
}

?>
