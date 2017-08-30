<?php
class ome_ctl_admin_shop extends desktop_controller {

    var $name = "店铺管理";
    var $workground = "channel_center";

    function index() {
        $Certi = base_certificate::get('certificate_id');
        $Node_id = base_shopnode::node_id('ome');
        $title = '前端店铺管理(证书：' . $Certi . '&nbsp;&nbsp;节点：' . $Node_id . ')';
        $actions = array(
                array('label' => '添加店铺', 'href' => 'index.php?app=ome&ctl=admin_shop&act=addterminal&finder_id=' . $_GET['finder_id'], 'target' => '_blank'),
                array('label' => '查看绑定关系', 'href' => 'index.php?app=ome&ctl=admin_shop&act=view_bindrelation', 'target' => '_blank'),);
        
        $this->finder('ome_mdl_shop', array(
            'title' => $title,
            'actions' => $actions,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
        ));
    }

    /**
     * 网店节点显示
     * @param null
     * @return null
     */
    public function shopnode() {
        $this->pagedata['node_id'] = base_shopnode::node_id($this->app->app_id);
        $this->pagedata['node_type'] = base_shopnode::node_type($this->app->app_id);

        $this->page('admin/system/shopnode.html');
    }

    /*
     * 添加前端店铺
     */

    function addterminal() {
        $this->_editterminal();
    }

    /*
     * 编辑前端店铺
     */

    function editterminal($shop_id) {
        $this->_editterminal($shop_id);
    }

    function _editterminal($shop_id=NULL) {
        $oShop = &$this->app->model("shop");
        $shoptype = ome_shop_type::get_shop_type();
        $shop_type = array();
        $i = 0;
        if ($shoptype)
            foreach ($shoptype as $k => $v) {
                $shop_type[$i]['type_value'] = $k;
                $shop_type[$i]['type_label'] = $v;
                $i++;
            }

        if ($shop_id) {
            $shop = $oShop->dump($shop_id);


            $shop_config = unserialize($shop['config']);

            if ($shop_config['password'])
                $shop_config['password'] = $oShop->aes_decode($shop_config['password']);

            $shop_tel = explode('-', strval($shop['tel']));
            $shop['tel_code'] = $shop_tel[0];
            $shop['tel_phone'] = strval($shop_tel[1]);
            $shop['tel_extension'] = strval($shop_tel[2]);

            $this->pagedata['shop'] = $shop;
            $this->pagedata['shop_config'] = $shop_config;
        }
        $this->pagedata['shop_type'] = $shop_type;
        $this->pagedata['title'] = '添加/编辑店铺';
        $this->singlepage("admin/system/terminal.html");
    }

    /**
     * 申请绑定关系
     * @param string $app_id
     * @param string $callback 异步返回地址
     * @param string $api_url API通信地址
     */
    function apply_bindrelation($app_id='ome', $callback='', $api_url='') {

        $this->Certi   = base_certificate::get('certificate_id');
        $this->Token   = base_certificate::get('token');
        $this->Node_id = base_shopnode::node_id($app_id);

        $token             = $this->Token;
        $sess_id           = kernel::single('base_session')->sess_id();
        $apply['certi_id'] = $this->Certi;

        if ($this->Node_id) $apply['node_idnode_id'] = $this->Node_id;
        $apply['sess_id'] = $sess_id;

        $str = '';
        ksort($apply);
        foreach ($apply as $key => $value) {
            $str.=$value;
        }

        $apply['certi_ac'] = md5($str . $token);

        $Ofunc = kernel::single('ome_rpc_func');
        $app_xml = $Ofunc->app_xml();
        $api_v   = $app_xml['api_ver'];

        //给矩阵发送session过期的回打地址。
        $sess_callback = kernel::base_url(true).kernel::url_prefix().'/openapi/ome.shop/shop_session';

        $params = array(
            'source'        => 'apply',
            'api_v'         => $api_v,
            'certi_id'      => $apply['certi_id'],
            'node_id'       => $apply['node_idnode_id'],
            'sess_id'       => $apply['sess_id'],
            'certi_ac'      => $apply['certi_ac'],
            'callback'      => $callback,
            'api_url'       => $api_url,
            'sess_callback' => $sess_callback,
            'show_type'     => 'shop|shopex',
        );

        $this->pagedata['license_iframe'] = sprintf('<iframe width="100%%" frameborder="0" height="99%%" id="iframe" onload="this.height=document.documentElement.clientHeight-4" src="%s" ></iframe>',MATRIX_RELATION_URL . '?' . http_build_query($params));

        // $this->pagedata['license_iframe'] = '<iframe width="100%" frameborder="0" height="99%" id="iframe" onload="this.height=document.documentElement.clientHeight-4" src="' . MATRIX_RELATION_URL . '?source=apply&api_v='.$api_v.'&certi_id=' . $apply['certi_id'] . '&node_id=' . $apply['node_idnode_id'] . '&sess_id=' . $apply['sess_id'] . '&certi_ac=' . $apply['certi_ac'] . '&callback=' . $callback . '&api_url=' . $api_url .'&show_type=shop|shopex&sess_callback='.$sess_callback.'" ></iframe>';

        $this->display('admin/system/apply_terminal.html');
    }

    /*
     * 查看绑定关系
     */

    function view_bindrelation() {
        $this->Certi = base_certificate::get('certificate_id');
        $this->Token = base_certificate::get('token');
        $this->Node_id = base_shopnode::node_id('ome');
        $token = $this->Token;
        $sess_id = kernel::single('base_session')->sess_id();
        $apply['certi_id'] = $this->Certi;
        $apply['node_idnode_id'] = $this->Node_id;
        $apply['sess_id'] = $sess_id;
        $str = '';
        ksort($apply);
        foreach ($apply as $key => $value) {
            $str.=$value;
        }
        $apply['certi_ac'] = md5($str . $token);

        $Ofunc = kernel::single('ome_rpc_func');
        $app_xml = $Ofunc->app_xml();
        $api_v = $app_xml['api_ver'];

        $callback = urlencode(kernel::openapi_url('openapi.ome.shop', 'shop_callback', array('shop_id' => $shop_id)));
        $api_url = kernel::base_url(true) . kernel::url_prefix() . '/api';
        $api_url = urlencode($api_url);
        $op_id = kernel::single('desktop_user')->get_login_name();
        $op_user = kernel::single('desktop_user')->get_name();
        $params = '&op_id=' . $op_id . '&op_user=' . $op_user;
        $show_alipay_subscribe_auth = 0;
        if (app::get('finance')->is_installed()) {
            $show_alipay_subscribe_auth = 1;
        }

        $params = array(
            'op_id'                      => $op_id,
            'op_user'                    => $op_user,
            'source'                     => 'accept',
            'show_alipay_subscribe_auth' => $show_alipay_subscribe_auth,
            'api_v'                      => $api_v,
            'certi_id'                   => $apply['certi_id'],
            'node_id'                    => $this->Node_id,
            'sess_id'                    => $apply['sess_id'],
            'certi_ac'                   => $apply['certi_ac'],
            'callback'                   => $callback,
            'api_url'                    => $api_url,
            'show_type'                  => 'shop|shopex',
        );

        // echo '<title>查看绑定关系</title><iframe width="100%" height="95%" frameborder="0" src=' . MATRIX_RELATION_URL . '?source=accept&show_alipay_subscribe_auth='.$show_alipay_subscribe_auth.'&api_v='.$api_v.'&certi_id=' . $apply['certi_id'] . '&node_id=' . $this->Node_id . '&sess_id=' . $apply['sess_id'] . '&certi_ac=' . $apply['certi_ac'] . '&callback=' . $callback . '&api_url=' . $api_url . $params . ' ></iframe>';

        echo sprintf('<title>查看绑定关系</title><iframe width="100%%" height="95%%" frameborder="0" src="%s" ></iframe>',MATRIX_RELATION_URL . '?' . http_build_query($params));
    }

    function saveterminal() {
        $oShop = &$this->app->model("shop");

        $url = 'index.php?app=ome&ctl=admin_shop&act=index';
        $this->begin($url);
        $svae_data = $_POST['shop'];

        if (!$svae_data['old_shop_bn']) {
            $shop_detail = $oShop->dump(array('shop_bn' => $svae_data['shop_bn']), 'shop_bn');
            if ($shop_detail['shop_bn']) {
                $this->end(false, app::get('base')->_('编码已存在，请重新输入'));
            }
        }

        $shop_detail = $oShop->dump(array('shop_id' => $svae_data['shop_id']), 'config');

        //表单验 证
        if (strlen($svae_data['zip']) <> '6') {
            $this->end(false, app::get('base')->_('请输入正确的邮编'));
        }
        //固定电话与手机必填一项
        $gd_tel = str_replace(" ", "", $svae_data['tel']);
        $mobile = str_replace(" ", "", $svae_data['mobile']);
        if (!$gd_tel && !$mobile) {
            $this->end(false, app::get('base')->_('固定电话与手机号码必需填写一项'));
        }
        $pattern = "/^400\d{7}$/";
        $pattern1 = "/^\d{1,4}-\d{7,8}(-\d{1,6})?$/i";
        if ($gd_tel) {
            $_rs = preg_match($pattern, $gd_tel);
            $_rs1 = preg_match($pattern1, $gd_tel);
            if ((!$_rs) && (!$_rs1)) {
                $this->end(false, app::get('base')->_('请填写正确的固定电话号码'));
            }
        }
        $pattern2 = "/^\d{8,15}$/i";
        if ($mobile) {
            if (!preg_match($pattern2, $mobile)) {
                $this->end(false, app::get('base')->_('请输入正确的手机号码'));
            }
            if ($mobile[0] == '0') {
                $this->end(false, app::get('base')->_('手机号码前请不要加0'));
            }
        }

        $config = unserialize($shop_detail['config']);
        $config['url'] = $svae_data['config']['url'];
        $config['account'] = $svae_data['config']['account'];
        $config['password'] = $svae_data['config']['password'];
        $svae_data['config'] = serialize($config);
        $rt = $oShop->save($svae_data);
        $rt = $rt ? true : false;

        # 新增店铺 自动绑定仓库
        if ($rt && !$svae_data['old_shop_bn']) {
            $rows = app::get('ome')->model('branch')->getAllBranchs('branch_id,branch_bn,attr');
            foreach ($rows as $key=>$row) {
                if ($row['attr'] == 'true') {
                    ome_shop_branch::update_relation($row['branch_bn'],array($svae_data['shop_bn']),$row['branch_id'],true);
                }
            }
        }
         //发送短信签名注册
        if (defined('APP_TOKEN') && defined('APP_SOURCE')) {
            base_kvstore::instance('taoexlib')->fetch('account', $account);
            if (unserialize($account)) {
                $sms_sign = '【'.$svae_data['name'].'】';
                kernel::single('taoexlib_request_sms')->newoauth_request(array('sms_sign'=>$sms_sign));
            }
        }
        //
         //发送短信签名注册
        if (defined('APP_TOKEN') && defined('APP_SOURCE')) {
            base_kvstore::instance('taoexlib')->fetch('account', $account);
            if (unserialize($account)) {
                $sms_sign = '【'.$svae_data['name'].'】';
                kernel::single('taoexlib_request_sms')->newoauth_request(array('sms_sign'=>$sms_sign));
            }
        }
        //
        $this->end($rt, app::get('base')->_($rt ? '保存成功' : '保存失败'));
    }

    /**
     * 手动解除绑定关系
     * @access public
     * @param string $shop_id
     */
    public function unbind() {
        $shop_id = addslashes($_GET['shop_id']);
        $finder_id = addslashes($_GET['finder_id']);
        if ($_GET['unbind'] == 'true') {
            $this->begin('');
            $shopObj = &app::get('ome')->model('shop');
            $update_data = array('node_id' => '', 'node_type' => '');
            $filter = array('shop_id' => $shop_id);
            $return = $shopObj->update($update_data, $filter);
            $return = $return ? true : false;
            $this->end($return, app::get('base')->_($return ? '解除成功' : '解除失败'));
        } else {
            $this->pagedata['finder_id'] = $finder_id;
            $this->pagedata['shop_id'] = $shop_id;
            $this->display('admin/system/unbind_terminal.html');
        }
    }

    function request_order() {
        $this->begin('index.php?app=ome&ctl=admin_shop&act=index');
        if ($_POST['start_time'] && $_POST['end_time']) {
            $oShop = &app::get('ome')->model("shop");
            $shop_id = $_POST['shop_id'];
            $shop = $oShop->dump($shop_id, 'shop_type,node_id');
            if (!$shop) {
                $this->end(false, app::get('base')->_('前端店铺信息不存在'));
            }
            $start_time = strtotime($_POST['start_time'] . ' 00:00:00');
            $end_time = strtotime($_POST['end_time'] . ' 23:59:59');
            if ($start_time && $end_time) {
                $diff_time = ($end_time - $start_time) / (60 * 60 * 24);
                if ($diff_time < 0) {
                    $this->end(false, app::get('base')->_('结束日期不能小于开始日期'));
                }

                if ($diff_time > 8) {
                    $this->end(false, app::get('base')->_('只能下载7天之内的订单'));
                }
                foreach (kernel::servicelist('service.order') as $object => $instance) {
                    if (method_exists($instance, 'update_order')) {
                        $instance->notify_get_order($shop, $start_time, $end_time);
                    }
                }
                $this->end(true, app::get('base')->_('同步成功'));
            } else {
                $this->end(false, app::get('base')->_('请正确填写开始时间和结束时间'));
            }
        } else {
            $this->end(false, app::get('base')->_('请选择开始时间和结束时间'));
        }
    }

    /**
     * 保存前端回写设置
     *
     * @param void
     * @return void
     */
    function request_config() {

        $this->begin('index.php?app=ome&ctl=admin_shop&act=index');

        if (!empty($_REQUEST['shop_id']) && !empty($_REQUEST['request_config'])) {

            $request_config = strtolower($_REQUEST['request_config']);
            app::get('ome')->setConf('request_auto_stock_' . $_REQUEST['shop_id'], $request_config);
            $this->end(true, app::get('base')->_('保存成功'));
        } else {

            $this->end(false, app::get('base')->_('输入的参数有误，请重新输入后再试！'));
        }
    }

    /*
    *获取前端订单详情
    */
    function sync_order()
    {
        if(empty($_POST['order_id'])){
            echo json_encode(array('rsp'=>'fail','msg'=>'订单号不能为空!'));exit;
        }
        if(empty($_POST['shop_id'])){
            echo json_encode(array('rsp'=>'fail','msg'=>'店铺ID不能为空!'));exit;
        }

        if(isset($_POST['order_type'])){
            $order_type = $_POST['order_type'];
        }else{
            $order_type = 'direct';
        }

        $Oorders = $this->app->model('orders');

        $filter = array('order_bn'=>trim($_POST['order_id']));

        $order = $Oorders->dump($filter,'outer_lastmodify');

        if($order){
            $Oorders->update(array('outer_lastmodify'=>($order['outer_lastmodify']-1)),$filter);
        }

        //$rpc_order = kernel::single("ome_rpc_request_order");

        //$rsp_data = $rpc_order->get_order_detial($_POST['order_id'],$_POST['shop_id'],$order_type);
        $rsp_data = kernel::single('apibusiness_router_request')->setShopId($_POST['shop_id'])->get_order_detial($_POST['order_id'],$order_type);
        if($rsp_data['rsp'] == 'succ')
        {
            $obj_syncorder = kernel::single("ome_syncorder");
            $sdf_order = $rsp_data['data']['trade'];
            if($obj_syncorder->get_order_log($sdf_order,$_POST['shop_id'],$msg)){
                echo json_encode(array('rsp'=>'succ'));exit;
            }else{
                echo json_encode(array('rsp'=>'fail','msg'=>$msg));exit;
            }
        }
        else
        {
            echo json_encode(array('rsp'=>'fail','msg'=>$rsp_data['err_msg']?$rsp_data['err_msg']:"同步订单失败。"));exit;
        }
    }

     
}

?>
