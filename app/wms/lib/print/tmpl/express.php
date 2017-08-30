<?php
class wms_print_tmpl_express{

	public $smarty = null;

	static $singleton = null;

    static $logi_list = array('amazon','dangdang');//需要根据物流公司自定义快递单输入信息列表

    static function instance($logi,$controller){

        $logi = strtolower($logi);

    	if(self::$singleton[$logi] === null){
            if(in_array($logi,self::$logi_list)){
                self::$singleton[$logi] = kernel::single('wms_print_tmpl_logi_'.$logi,$controller);
            }else{
                self::$singleton[$logi] = kernel::single('wms_print_tmpl_express',$controller);
            }
    	}

    	return self::$singleton[$logi];
    }

    public function __construct($controller){

        $this->smarty = $controller;
    }

    public function setParams( $params = array() ){
        return $this;
    }

    public function getTmpl(){
        $this->smarty->pagedata['userAgent'] = $this->getUserAgent();
        //新版快递单打印
        if (app::get('logisticsmanager')->is_installed()) {
            if($this->smarty->pagedata['printTmpl']['template_type']=='normal') {
                $this->smarty->singlepage("admin/delivery/express_print_normal.html");
            } else {
                $this->smarty->singlepage("admin/delivery/express_print_electron.html");
            }
        } else {
            $this->smarty->singlepage("admin/delivery/express_print.html");
        }
	}

    /**
     * 获得浏览器版本
     * Enter description here ...
     */
    public function getUserAgent() {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        $brower = array('brower' => 'Other', 'ver' => '0', 'type' => 2);
    
        if (strpos($agent, "MSIE 10.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '10.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 9.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '9.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 8.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '8.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 7.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '7.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 6.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '6.0', 'type' => 1);
        }
        elseif (strpos($agent, "Trident")) {
            //IE11以后的版本
            $str = substr($agent, strpos($agent, 'rv:') + strlen('rv:'));
            $ver = substr($str, 0, strpos($str, ')'));
            $brower = array('brower' => 'Ie', 'ver' => $ver, 'type' => 1);
        }
        elseif (strpos($agent, "Chrome")) {
            $str = substr($agent, strpos($agent, 'Chrome/') + strlen('Chrome/'));
            $verInfo = explode(" ", $str);
            $brower = array('brower' => 'Chrome', 'ver' => $verInfo[0], 'type' => 2);
        }
        elseif (strpos($agent, "Firefox")) {
            $str = substr($agent, strpos($agent, 'Firefox/') + strlen('Firefox/'));
            $brower = array('brower' => 'Firefox', 'ver' => $str, 'type' => 2);
        }
        return $brower;
    }
}