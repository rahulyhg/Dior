<?php
class ome_view_helper{

    function __construct(&$app){
        $this->app = $app;
    }
    
    public function function_desktop_header($params, &$smarty){
       
        return $smarty->fetch('admin/include/header.tpl',$this->app->app_id);
    }

    public function function_desktop_footer($params, &$smarty){
        $base_host = kernel::single('base_request')->get_host();
        if(strpos($base_host,'.m.fenxiaowang.com') !== false){
            //$smarty->pagedata['newLogo'] = '一体化分销平台';
        }elseif(strpos($base_host,'.erp.eqimingxing.com') !== false){
            //$smarty->pagedata['newLogo'] = '分销动力ERP';
        }elseif(strpos($base_host,'.erp.shopexdrp.cn') !== false){
            //$smarty->pagedata['newLogo'] = '电商ERP';
        }
        $info = array('ver' => 0, 'warning' => 'false', 'userDegree' => 0);
        if ($base_host) {
            base_kvstore::instance('ome_desktop')->fetch('host_with', $host_with);
            if (empty($host_with)) {
                $api = kernel::single('ome_saasmonitorapi');
                $api->appkey = 'taoguan';
                $api->secretKey = '49F4589687E79D815339B13A73E5FBB4';
                $api->format = 'json';
                //测试店铺数据
//                $base_host = 'njrhyw.tg.taoex.com';
                $params = array('host_domain' => $base_host);
                $method = 'analysis.get_hostwithinfo';
                $result = $api->execute($method, $params);
                if (isset($result) && $result->success == 'true') {
                    $data = $this->_objectToArray($result);
                    $host_with = $data['data'];
                    //有效期到每天的23:59:58
//                    $ttl = max(0, strtotime(date("Y-m-d 00:00:00", strtotime("+1 days"))) - 2 - time());
                    //有效期30分钟
                    $ttl = 1800;
                    base_kvstore::instance('ome_desktop')->store('host_with', $host_with, $ttl);
                    $info = array('ver' => $host_with['ver'], 'warning' => $host_with['warning'], 'userDegree' => $host_with['userDegree']);
                }
            }
            else {
                $info = array('ver' => $host_with['ver'], 'warning' => $host_with['warning'], 'userDegree' => $host_with['userDegree']);
            }
        }
        //提示信息初始化
        $logoMesaage = array('ver' => '', 'warning' => '', 'warning_alert' => '');
        $verTitle = array(
            0 => "企业版用户\\n存在更高级版本：旗舰版和协同版。\\n旗舰版主要增强：\\n1、针对财务模块有更加高级的应用，财务对账、自动支付宝流水、费用对账\\n2、开放标准数据接口，用于与企业线下系统进行集成\\n3、针对天猫供销平台的深度应用\\n协同版在旗舰版的基础上，增加了与第三方仓储发货业务的支持。对接了市面上主流第三方仓储公司，并且提供标准接口，可以让企业对接特殊的WMS。",
            1 => "旗舰版用户\\n尊敬的旗舰版客户。\\n如果贵公司有第三方仓储发货需求，可以升级到我们 “协同版”\\n对接了市面上主流第三方仓储公司，并且提供标准接口，可以让企业对接特殊的WMS。",
            2 => "协同版用户\\n尊敬的协同版客户，贵公司是我们最高级别的客户，希望贵公司业务越做越大。感谢对我们公司的支持。",
            3 => "TP版用户\\n尊敬的协同版客户，贵公司是我们最高级别的客户，希望贵公司业务越做越大。感谢对我们公司的支持。",
        );
        //警告标题
        $warningTitle = array(
            0 => "尊贵的客户，系统经过自检发现目前贵公司的业务发展和膨胀的速度已经达到系统最佳性能瓶颈值。\\n为了避免意外情况，以及更好的系统体验，保障业务流畅的运作。\\n强烈建议升级到“保障部署”避免影响公司的高速发展。\\n升级请咨询：4008213016　或　021-33251818-6603",
            1 => "保障部署\\n 尊敬的保障部署客户，请安心拓展贵公司的业务，系统性能已经受到保障。现在正享受着我们公司为贵公司提供的独立的运维服务。\\n希望贵公司业务越做越大。感谢对我们公司的支持。",
        );
        //警告标题(alert)
        $warningTitleAlert = array(
            0 => "尊贵的客户，系统经过自检发现目前贵公司的业务发展和膨胀的速度已经达到系统最佳性能瓶颈值。\\n为了避免意外情况，以及更好的系统体验，保障业务流畅的运作。\\n强烈建议升级到“保障部署”避免影响公司的高速发展。\\n升级请咨询：4008213016　或　021-33251818-6603",
            1 => "保障部署\\n 尊敬的保障部署客户，请安心拓展贵公司的业务，系统性能已经受到保障。现在正享受着我们公司为贵公司提供的独立的运维服务。\\n希望贵公司业务越做越大。感谢对我们公司的支持。",
        );
        $userDegreeTitle = array(
            0 => "普通部署\\n目前系统性能稳定，目前可以支撑贵公司的业务发展\\n贵公司还可以成为我们的“保障部署”会员\\n保障部署享受如下特权:\\n1、独立受保护的计算资源与空间\\n2、独立的保障运维团队进行优先处理\\n3、特殊的监控、维护、扩展保障体系\\n4、保障系统性能稳定。",
            1 => "保障部署\\n尊敬的保障部署客户，请安心拓展贵公司的业务，系统性能已经受到保障。现在正享受着我们公司为贵公司提供的独立的运维服务。\\n希望贵公司业务越做越大。感谢对我们公司的支持。"
        );
        //版本logo
        $verLogo = rtrim($this->app->res_full_url, "/") . '/icons/icon_ver_' . $info['ver'] . '.gif';
        $logoMesaage['ver'] = $verTitle[$info['ver']];
        //警告logo
        $warningLogo = '';
        if ($info['warning'] == 'true') {
            $warningLogo = rtrim($this->app->res_full_url, "/") . '/icons/icon_warning_'.$info['userDegree'].'.gif';
            $logoMesaage['warning'] = $warningTitle[$info['userDegree']];
            $logoMesaage['warning_alert'] = $warningTitleAlert[$info['userDegree']];
        }
        else {
            $warningLogo = rtrim($this->app->res_full_url, "/") . '/icons/icon_user_degree_'.$info['userDegree'].'.gif';
            $logoMesaage['warning'] = $userDegreeTitle[$info['userDegree']];
            $logoMesaage['warning_alert'] = $warningTitleAlert[$info['userDegree']];
        }
        //第一次进入系统弹窗，时隔30分钟弹窗
        if ($info['warning'] == 'true') {
            kernel::single('base_session')->start();
            //SESSION不存在
            if (!array_key_exists('ome_desktop_warning', $_SESSION) || empty($_SESSION['ome_desktop_warning'])) {
                $_SESSION['ome_desktop_warning'] = 'true';
                base_kvstore::instance('ome_desktop')->store('warning', $_SESSION['ome_desktop_warning'], 1800);
                $session_warning = 'false';
            }
            else {
                $session_warning = 'true';
            }
            //KV不存在
            base_kvstore::instance('ome_desktop')->fetch('warning', $ome_desktop_warning);
            if (!$ome_desktop_warning) {
                $session_warning = 'false';
                //清除SESSION
                $_SESSION['ome_desktop_warning'] = null;
                unset($_SESSION['ome_desktop_warning']);
            }
        }
        else {
            $session_warning = 'other';
        }
        $smarty->pagedata['verLogo'] = $verLogo;
        $smarty->pagedata['warningLogo'] = $warningLogo;
        $smarty->pagedata['logoMesaage'] = $logoMesaage;
        $smarty->pagedata['userDegree'] = $info['userDegree'];
        $smarty->pagedata['warning'] = $info['warning'];
        $smarty->pagedata['session_warning'] = $session_warning;
        $smarty->pagedata['verCode'] = $info['ver'];
        return $smarty->fetch('admin/include/footer.tpl',$this->app->app_id);
    }
    /**
     * 对象转为数组
     * @param Object $e 对象数组
     */
    private function _objectToArray($e){ 
        $e = (array)$e; 
        foreach($e as $k=>$v) { 
            if( gettype($v)=='resource' ) return;
            if( gettype($v)=='object' || gettype($v)=='array' ) $e[$k]=(array) $this->_objectToArray($v); 
        } 
        return $e; 
    } 
}
