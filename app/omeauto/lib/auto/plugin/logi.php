<?php
/**
 * 设置并检查物流号
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

class omeauto_auto_plugin_logi extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = true;

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

    /**
     * 状态码
     * @var integer
     */
    protected $__STATE_CODE = omeauto_auto_const::__LOGI_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {
        /* 店铺物流绑定 start */
        $shopCropObj = app::get('ome')->model('shop_dly_corp');
        $shopObj = &app::get('ome')->model("shop");

        $orders = $group->getOrders();
        foreach($orders as $val){
            $filter['shop_id'] = $val['shop_id'];
            $filter['crop_name'] = $val['shipping'];
            break;
        }

        $shopData = $shopObj->dump($filter['shop_id']);
        $cropSet = false;
        if($shopData['crop_config']['cropBind']==1){
            $shopCrop = $shopCropObj->dump($filter);
            if($shopCrop['corp_id']>0){
                $cropObj = app::get('ome')->model('dly_corp');
                $cropData = $cropObj->dump($shopCrop['corp_id']);
                if($cropData['corp_id']>0 && $cropData['disabled']=='false'){
                    $group->setDlyCorp($cropData);
                    $cropSet = true;
                }
            }
            if($cropSet==false && $shopData['crop_config']['sysCrop']!=1){//未匹配物流且系统不自动选择
                $group->setOrderStatus('*', $this->getMsgFlag());
                $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
                $cropSet = true;

            }
        }
        /* 店铺物流绑定 end */

        if(!$cropSet || $cropSet==false){
            //自动匹配物流公司
            $this->initCropData();
            $corpId = $this->markSelectDlyCorp($group);

            if (!$corpId) {
                //$corpId = $this->autoSelectDlyCorp($group->getShipArea(), $group->getBranchId(),$confirmRoles);
                $corpId = kernel::single('logistics_rule')->autoMatchDlyCorp($group->getShipArea(),$group->getBranchId(),$group->getWeight(),$group->getShopType(),$filter['shop_id']);
                if ($corpId > 0 && self::$corpList[$corpId]) {
                    //能匹配到物流公司

                    $group->setDlyCorp(self::$corpList[$corpId]);
                } else {
                    //不能匹配
                    $group->setOrderStatus('*', $this->getMsgFlag());
                    $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
                }
            } else {
                $channel_id = self::$corpList[$corpId]['channel_id'];
                if (!is_array($corpId) && (self::$corpList[$corpId]['tmpl_type']=='normal' || self::$channelType[$channel_id]=='ems' || (self::$channelType[$channel_id]=='wlb' && self::$corpList[$corpId]['shop_id']==$filter['shop_id']))) {
                    $mark = kernel::single('omeauto_auto_group_mark');
                    $corpId= $mark->fetchCorpId($corpId);
                    $group->setDlyCorp(self::$corpList[$corpId]);
                } else {
                    //不能匹配
                    $group->setOrderStatus('*', $this->getMsgFlag());
                    $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
                }
            }
        }
    }

     /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '无匹配物流';
    }

    /**
     * 根据收货地址自动匹配物流公司
     *
     * @param String $shipArea 送货地址
     * @return mixed
     */
     function autoSelectDlyCorp($shipArea, $branchId, &$confirmRoles) {
        $regionId = preg_replace('/.*:([0-9]+)$/is', '$1', $shipArea);
        $this->initCropData();
        $regionPath = self::$region[$regionId];

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

        //通过区域匹配可送达的物流公司
        $corpIds = $this->getCorpByArea($regionPath, $branchId);

        //在增加默认全部可送的快递公司
        if (empty($corpIds) && $confirmRoles['allDlyCrop'] != 1) {
            $corpIds = $this->getDefaultCorp($branchId);
        }

        //获取最佳物流
        $corpId = $this->getBestCorpId($corpIds);

        //根据设置返回物流公司ID
        return $corpId;
    }

    /**
     * 根据客服备注获取物流公司
     *
     * @param Void
     * @return mixed
     */
    private function markSelectDlyCorp(& $group) {

        $mark = kernel::single('omeauto_auto_group_mark');
        if (! $mark->useMark()) {

            return null;
        }
        $ret = array();
        $wCode = trim($mark->getCodeByFix('markDelivery'));
        foreach ($group->getOrders() as $order) {

            $markText = $this->getMark($order['mark_text']);

            $wRet = $mark->getMark($wCode, $markText);
            if (!empty($wRet)) {

                //$ret = array_merge($ret, $wRet);
                foreach ($wRet as  $wItem) {

                    if (!in_array($wItem, $ret)) {

                        $ret[] = $wItem;
                    }
                }
            }
        }

        //检查快递是否唯一，否则置为不可用
        if (!empty($ret)) {

             if (count($ret) == 1) {

                 return $ret[0];
             }  else {

                 $group->setOrderStatus('*', $mark->getMsgFlag('w'));
                 $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
                 return $ret;
             }

        } else {

            return false;
        }
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
     * 通过发货地区的地区路径，获取可匹配的快递公司
     *
     * @param String $regionPath 发货地区的地区路径
     * @return Array;
     */
    private function getCorpByArea($regionPath, $branchId) {

        $corpIds = array();
        //先查找有区域配置的快递公司
        if (!empty($regionPath)) {
            $regionIds = explode(',', $regionPath);
            array_shift($regionIds);
            array_pop($regionIds);

            foreach ($regionIds as $rId) {

                foreach(self::$corpArea as $corpId => $cRegion) {

                    if (in_array($rId, $cRegion) && (self::$corpList[$corpId]['branch_id'] == $branchId || self::$corpList[$corpId]['all_branch']=='true')) {

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

        //获取地区配置信息
        $regions = kernel::single('eccommon_regions')->getList('region_id,region_path');
        foreach ($regions as $row) {
            self::$region[$row['region_id']] = $row['region_path'];
        }
        unset($regions);

        //获取快递公司配置信息
        $corp = app::get('ome')->model('dly_corp')->getList('branch_id, all_branch, corp_id, name, type, is_cod, weight, channel_id, shop_id, tmpl_type', array('disabled' => 'false'), 0, -1, 'weight DESC');
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
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => 'BLUE', 'flag'=>'物' , 'msg' => '无法自动匹配物流公司');
    }

    /**
     * 获取用于快速审核的选项页，输出HTML代码
     *
     * @param void
     * @return String
     */
    public function getInputUI() {

        //获取快递公司配置信息
        $corpList = array('-1' => '自动匹配物流');
        $corp = app::get('ome')->model('dly_corp')->getList('corp_id, name, type, is_cod, weight', array('disabled' => 'false'), 0, -1, 'weight DESC');
        foreach($corp as $item) {
            $corpList[$item['corp_id']] = $item['name'];
        }
        unset($corp);

        if (empty($corpList)) {

            $result = "<span>您还没有设置可用的物流公司。</span>";
        } else {
            $result = "<span class='customTitle'>请选择指的物流公司：</sapn>\n<select name='customAuto[logi][customLogiId]'>\n";
            foreach ($corpList as $logiId => $cropName) {

                $result .= "<option value='{$logiId}'>{$cropName}</option>\n";
            }
            $result .= "</select>\n";
        }

        return $result;
    }
}