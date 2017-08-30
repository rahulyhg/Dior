<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class desktop_ctl_default extends desktop_controller{

    var $workground = 'desktop_ctl_dashboard';

    function index(){

        $this->_init_keyboard_setting();

        $desktop_user = kernel::single('desktop_user');

        $menus = $desktop_user->get_work_menu();
        $user_id = $this->user->get_id();
        $desktop_user->get_conf('fav_menus',$fav_menus);
        //默认显示5个workground
        $workground_count = (app::get('desktop')->getConf('workground.count'))?(app::get('desktop')->getConf('workground.count')-1):5;
        if(!$fav_menus){
            $i = 0;
            foreach((array)$menus['workground'] as $key=>$value){
                //if($i++>$workground_count) break;
                $fav_menus[] = $key;

            }
        }


        $obj = kernel::service('desktop_index_seo');
        if(is_object($obj) && method_exists($obj, 'title')){
            $title = $obj->title();
        }else{
            $title = app::get('desktop')->_('管理后台');
        }
        if(is_object($obj) && method_exists($obj, 'title_desc')){
            $title_desc = $obj->title_desc();
        }else{
            $title_desc = 'Powered By ShopEx';
        }


        $this->pagedata['title'] = $title;
        $this->pagedata['title_desc'] = $title_desc;
        $this->pagedata['session_id'] = kernel::single('base_session')->sess_id();
        $this->pagedata['uname'] = $this->user->get_login_name();
        $this->pagedata['param_id'] = $user_id;
        $this->pagedata['menus'] = $menus;
        $this->pagedata['fav_menus'] = (array)$fav_menus;
        $this->pagedata['shop_base']  = kernel::base_url(1);
        $this->pagedata['shopadmin_dir'] = ($_SERVER['REQUEST_URI']);
        $desktop_user->get_conf('shortcuts_menus',$shortcuts_menus);
        $this->pagedata['shortcuts_menus'] = (array)$shortcuts_menus;
        $desktop_menu = array();
        foreach(kernel::servicelist('desktop_menu') as $service){
            $array = $service->function_menu();
            $desktop_menu = (is_array($array)) ? array_merge($desktop_menu, $array) : array_merge($desktop_menu, array($array));
        }
        $this->pagedata['desktop_menu'] = (count($desktop_menu)) ? '<span>'.join('</span>|<span>', $desktop_menu).'</span>' : '';
        list($this->pagedata['theme_scripts'],$this->pagedata['theme_css']) =
            desktop_application_theme::get_files($this->user->get_theme());

        $this->Certi = base_certificate::get('certificate_id');
        $confirmkey = $this->setEncode($this->pagedata['session_id'],$this->Certi);
        $this->pagedata['certificate_url'] = "http://service.shopex.cn/info.php?sess_id=".urlencode($this->pagedata['session_id'])."&certi_id=".urlencode($this->Certi)."&version=ecstore&confirmkey=".urlencode($confirmkey)."&_key_=do";
        if(app::get('bizsuite')->is_actived()){
            $bind = app::get('bizsuite')->model('relation')->getList('shop_id',array('node_type'=>'bizsuite','status'=>'bind'));
            $oauth_info = app::get('bizsuite')->getConf('biz.oauth');
            if($bind && $oauth_info){
                $url = rtrim($oauth_info['client_url'], '/');
                if( ! preg_match('/^(http|https)/', $url) ) {
                    $url = 'http://'.$url;
                }
                $this->pagedata['cloud_url'] = $url;
            }
        }
        $this->display('index.html');

    }

    function setEncode($sess_id,$certi_id){
        $ENCODEKEY='ShopEx@License';
        $confirmkey = md5($sess_id.$ENCODEKEY.$certi_id);
        return $confirmkey;
    }

	public function set_open_api()
	{
		echo $this->openapi();exit;
	}

    private function openapi() {
        $params['certi_app']       = 'open.login';
        $this->Certi = base_certificate::get('certificate_id');
        $this->Token = base_certificate::get('token');
        $params['certificate_id']  = $this->Certi;
        $params['format'] = 'image';
        /** 增加反查参数result和反查基础地址url **/
        $code = md5(microtime());
        base_kvstore::instance('ecos')->store('net.login_handshake',$code);
        $params['result'] = $code;
        /** 得到框架的总版本号 **/
        //$app_xml = kernel::single('base_xml')->xml2array(file_get_contents(app::get('base')->app_dir.'/app.xml'),'base_app');
        $obj_apps = app::get('base')->model('apps');
        $tmp = $obj_apps->getList('*',array('app_id'=>'base'));
        $app_xml = $tmp[0];
        $params['version'] = $app_xml['local_ver'];
        $params['url'] = kernel::base_url(1);
        /** end **/
        $token = $this->Token;
        $str   = '';
        ksort($params);
        foreach($params as $key => $value){
            $str.=$value;
        }
        $params['certi_ac'] = md5($str.$token);
        $http = kernel::single('base_httpclient');
        $http->timeout = 6;
        $result = $http->post(
            LICENSE_CENTER_V,
            $params
        );
        //$this->pagedata['open_api_url'] = LICENSE_CENTER_V .'?'. utils::http_build_query( $params );
        $tmp_res = json_decode($result, 1);
        if ($tmp_res)
        {
            // 存在异常
            if ($tmp_res['res'] == 'fail')
            {
                $this->pagedata['open_api_url'] = $tmp_res['msg'];
            }
            else
            {
                if ($tmp_res['res'] == 'succ')
                    $this->pagedata['open_api_url'] = stripslashes($tmp_res['info']);
                else
                    $this->pagedata['open_api_url'] = stripslashes($tmp_res);
            }
        }
        else
            $this->pagedata['open_api_url'] = stripslashes($tmp_res);

		return $this->pagedata['open_api_url'];
    }



    function set_main_menu(){
        $desktop_user = new desktop_user();
        $workground = $_POST['workgrounds'];
        $desktop_user->set_conf('fav_menus',$workground);
        header('Content-Type:text/jcmd; charset=utf-8');

        echo '{success:"'.app::get('desktop')->_("保存成功！").'"
        }';
    }





    function allmenu(){
        $desktop_user = new desktop_user();
        $menus = $desktop_user->get_work_menu();
        $desktop_user->get_conf('shortcuts_menus',$shortcuts_menus);

        foreach($menus['workground'] as $k=>$v){
            $v['menu_group'] = $menus['menu'][$k];
            $workground_menus[$k]  = $v;
        }
        $this->pagedata['menus'] = $workground_menus;
        $this->pagedata['shortcuts_menus'] = (array)$shortcuts_menus;
        $this->display('allmenu.html');

    }

    function main_menu_define(){
        $desktop_user = kernel::single('desktop_user');

        $menus = $desktop_user->get_work_menu();
        $user_id = $this->user->get_id();
        $desktop_user->get_conf('fav_menus',$fav_menus);
        //默认显示5个workground
        $workground_count = (app::get('desktop')->getConf('workground.count'))?(app::get('desktop')->getConf('workground.count')-1):5;
        if(!$fav_menus){
            $i = 0;
            foreach((array)$menus['workground'] as $key=>$value){
                //if($i++>$workground_count) break;
                $fav_menus[] = $key;
            }
        }

        $this->pagedata['fav_menus'] = (array)$fav_menus;
        $this->pagedata['menus'] = $menus;
        $this->display('main_menu_define.html');

    }


    private function _init_keyboard_setting() {
        $desktop_user = kernel::single('desktop_user');
        $desktop_user->get_conf('keyboard_setting',$keyboard_setting);
        $o = kernel::single('desktop_keyboard_setting');
        $json = $o->get_setting_json( $keyboard_setting );
        $this->pagedata['keyboard_setting_json'] = $json;
    }


    public function keyboard_setting() {
        $desktop_user = kernel::single('desktop_user');
        if( $_POST['keyboard_setting'] ) {
            $desktop_user->set_conf('keyboard_setting',$_POST['keyboard_setting']);
            $this->_init_keyboard_setting();
            echo $this->pagedata['keyboard_setting_json'];exit;
        }

        $desktop_user->get_conf('keyboard_setting',$keyboard_setting);

        //初始化数据
        $o = kernel::single('desktop_keyboard_setting');
        $o->init_keyboard_setting_data( $setting,$keyword,$keyboard_setting );

        foreach( $setting as $key => &$_setting ) {
            foreach( $_setting as &$row ) {
                if( $key!='导航菜单上的栏目' ) {
                    $default = array('ctrl','shift');
                    $o->set_default_control( $default,$row );
                } else {
                    $default = array('alt');
                    $o->set_default_control( $default,$row );
                }
            }
        }

        $this->pagedata['form_action_url'] = $this->app->router()->gen_url( array('app'=>'desktop','act'=>'keyboard_setting','ctl'=>'default') );
        $this->pagedata['keyword'] = $keyword;
        $this->pagedata['setting'] = $setting;
        $this->display('keyboard_setting.html');
    }


    function workground(){
        $wg = $_GET['wg'];
        if(!$wg){
            echo app::get('desktop')->_("参数错误");exit;
        }
        $user = new desktop_user();
        $menus = $this->app->model('menus');
        $group = $user->group();
        $aPermission = array();
        foreach((array)$group as $val){
            #$sdf_permission = $menus->dump($val);
            $aPermission[] = $val;
        }

        if($user->is_super()){
            $sdf = $menus->getList('*',array('menu_type' => 'menu','workground' => $wg));
        }
        else{
            $sdf = $menus->getList('*',array('menu_type' => 'menu','workground' => $wg,'permission' => $aPermission));
        }

        foreach((array)$sdf as $value){
            $url = $value['menu_path'];
            if($value['display'] == 'true'){
                $url_params = unserialize($value['addon']);
                if(count($url_params['url_params'])>0){
                    foreach((array)$url_params['url_params'] as $key => $val){
                        $parmas =$params.'&'.$key.'='.$val;
                    }
                }
                $url = $value['menu_path'].$parmas; break;
            }

        }
        $this->redirect('index.php?'.$url);

    }


    function alertpages(){
        $this->pagedata['goto'] = urldecode($_GET['goto']);
        $this->singlepage('loadpage.html');
    }



    function set_shortcuts(){
        $desktop_user = new desktop_user();
        $_POST['shortcuts'] = ($_POST['shortcuts']?$_POST['shortcuts']:array());
        foreach($_POST['shortcuts'] as $k=>$v){
            list($k,$v) = explode('|',$v);
            $shortcuts[$k] = $v;
        }
        $desktop_user->set_conf('shortcuts_menus',$shortcuts);
        header('Content-Type:text/jcmd; charset=utf-8');
        echo '{success:"'.app::get('desktop')->_("设置成功").'"}';
    }






    function status(){

        set_time_limit(0);
        ob_start();
/*        if($_POST['events']){
            foreach($_POST['events'] as $worker=>$task){
                foreach(kernel::servicelist('desktop_task.'.$worker) as $object){
                    $object->run($task,$this);
                }
            }
        }
*/
        $flow = &$this->app->model('flow');
        if($flow->fetch_role_flow($this->user)){
            echo '<script>alert("'.app::get('desktop')->_("您有新短消息！").'");</script>';
        }

        //当日回写错误提醒条数
        //$this->_get_warn_num();

        //系统通知 desktop  未读条数
        //$this->_get_notify_num();

        $output = ob_get_contents();
        ob_end_clean();
        header('Content-length: '.strlen($output));
        header('Connection: close');
        echo $output;

        /*
        $taskObj = kernel::single('taoexlib_task_limit');
        if($taskObj->available()){
            $taskObj->setStatus(taoexlib_task_limit::$__RUNNING);
            app::get('base')->model('queue')->flush();
            kernel::single('base_misc_autotask')->trigger();
            $taskObj->setStatus(taoexlib_task_limit::$__FINISH);
        }
        */

        kernel::single('base_session')->close(false);
    }

    function desktop_events(){

        if($_POST['events']){
            foreach($_POST['events'] as $worker=>$task){
                foreach(kernel::servicelist('desktop_task.'.$worker) as $object){
                    $object->run($task,$this);
                }
            }
        }
    }

    public function _get_notify_num() {
        $count = app::get('base')->model('rpcnotify')->count( array('status'=>'false') );
        if( $count ) {
            $js = '$$("#topbar .notify_num").getParent().setStyle("display","inline");';
        }
        echo '<script>'. $js .'$$("#topbar .notify_num")[0].set(\'text\',"'. ($count ? "($count)": '') .'");</script>';
    }

    public function _get_warn_num() {
        $start_time = strtotime(date("Y-m-d"));
        $up_time = array($start_time,time());
        $orderObj = app::get('ome')->model('orders');
        $count = $orderObj->count( array('createway'=>'matrix','sync'=>'fail','up_time|between'=>$up_time) );
        if( $count ) {
            $js = '$("syncwarn").setStyle("display","block");';

            $shipped_count = $orderObj->count( array('createway'=>'matrix','sync'=>'fail','sync_fail_type'=>'shipped','up_time|between'=>$up_time) );
            $params_count = $orderObj->count( array('createway'=>'matrix','sync'=>'fail','sync_fail_type'=>'params','up_time|between'=>$up_time) );
            $other_count = $orderObj->count( array('createway'=>'matrix','sync'=>'fail','sync_fail_type'=>array('none','unbind'),'up_time|between'=>$up_time) );
            $countjs = '$("sync-shipped-count").set(\'text\',"'.$shipped_count.'");$("sync-params-count").set(\'text\',"'.$params_count.'");$("sync-other-count").set(\'text\',"'.$other_count.'");';
        }
        echo '<script>'. $js . $countjs .'$$("#syncwarn .warn_num")[0].set(\'text\',"'. ($count ? "($count)": '') .'");</script>';
    }

	public function about_blank(){
		echo '<html><head></head><body>ABOUT_BLANK_PAGE</body></html>';
	}

    function clear_session(){
        $this->user->id = 0;
        $_SESSION = array();
        echo 'succ';
    }
}
