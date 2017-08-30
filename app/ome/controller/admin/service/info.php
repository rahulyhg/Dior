<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_ctl_admin_service_info extends desktop_controller{
    
    private $service;
    
    /**
     * @过期提醒菜单点击详细页
     * @access public
     * @param void
     * @return void
     */
    public function index() {
        $service = new ome_saas_service(new ome_saas_site());

        $info = $service->getInfo();

        //保障部署天数
        $ensureDay = $this->getEnsureDay();
        //保障部署信息
        $ensureInfo = $this->getEnsureInfo();
        $this->pagedata['ensureDay'] = $ensureDay;
        $this->pagedata['ensureInfo'] = $ensureInfo;

        $this->pagedata['info'] = $info;
        $this->pagedata['days'] = $service->getValidityDate();
        
        $this->display('admin/service/info.html');
    }

    /**
     * @验证服务是否过期
     * @access public
     * @param void
     * @return void/json
     */
    public function validity() {
        $days = $this->getServiceDays();
        $ensureDay = $this->getEnsureDay();
        if($days === false && $ensureDay === false) {
            return array();
        }
        
        $msg = $this->toMsg($days, $ensureDay);
        $ensureMsg = $this->toMsg($ensureDay);
        echo json_encode(array('days'=>$days, 'msg'=>$msg['msg1'], 'ensureDay' => $ensureDay, 'ensureMsg' => $msg['msg2']));
    }

    /**
     * @根据服务日期期限弹窗显示的提示信息页
     * @access public
     * @param void
     * @return void
     */
    public function alert() {
        $days = $this->getServiceDays();

        $this->pagedata['days'] = $days;

        $ensureDay = $this->getEnsureDay();
        if ($ensureDay === false) {
            $ensureDay = -1;
        }
        $this->pagedata['ensureDay'] = $ensureDay;
        
        $this->display('admin/service/alert.html');
    }

    /**
     * @获取服务日期期限
     * @access public
     * @param void
     * @return void
     */
    private function getServiceDays() {
        $this->service = new ome_saas_service(new ome_saas_site());
        
        return $this->service->getValidityDate();
    }

    /**
     * @根据服务日期期限显示相应的提示信息
     * @access public
     * @param int $days 剩余的使用天数
     * @return void
     */
    private function toMsg($days1, $days2 = false) {
        $msg = array('msg1' => '服务信息', 'msg2' => '服务信息');
        if ($days1 === false) {
            $msg['msg1'] = '服务信息';
        }
        elseif ($days1 === 0) {
            $msg['msg1'] = '系统服务最后一天，请及时续约';
        }
        elseif ($days1 < 0) {
            $msg['msg1'] = '系统服务已过期'. abs($days1) . '天';
        }
        else {
            $msg['msg1'] = '系统服务还有'. $days1 .'天到期';
        }

        if ($days2 === false) {
            $msg['msg2'] = '服务信息';
        }
        elseif ($days2 === 0) {
            $msg['msg2'] = '保障服务最后一天，请及时续约';
        }
        elseif ($days2 < 0) {
            $msg['msg2'] = '保障服务已过期'. abs($days2) . '天';
        }
        else{
            $msg['msg2'] = '保障服务还有'. $days2 .'天到期';
        }
        return $msg;
//        if($days === false) {
//            $msg = '服务信息';
//        }elseif($days === 0) {
//            $msg = '最后一天，请及时续约';
//        } elseif($days < 0) {
//            $msg = '已过期'. abs($days) . '天';
//        } else {
//			$msg = '还有'. $days .'天到期';
//        }
//        
//        return $msg;
    }
    /**
     * 获得保障天数
     */
    public function getEnsureDay() {
        base_kvstore::instance('ome_desktop')->fetch('host_with', $host_with);
        if ($host_with) {
            if ($host_with['ver'] < 2 && $host_with['userDegree'] == 1) {
                $ensureEt = mktime(0, 0, 0, date("m", $host_with['ensure_et']), (date("d", $host_with['ensure_et']) + 1), date("Y", $host_with['ensure_et'])) - 1;
                $days = $ensureEt - time();
                $days = intval($days / 86400);
            }
            else {
                $days = false;
            }
        }
        else {
            $days = false;
        }
        return $days;
    }
    /**
     * 获得保障部署信息
     */
    public function getEnsureInfo() {
        base_kvstore::instance('ome_desktop')->fetch('host_with', $host_with);
        if ($host_with) {
            $host_with['ensure_st_title'] = date("Y-m-d H:i:s", $host_with['ensure_st']);
            $host_with['ensure_et_title'] = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m", $host_with['ensure_et']), (date("d", $host_with['ensure_et']) + 1), date("Y", $host_with['ensure_et'])) - 1);
        }
        return $host_with;
    }

}