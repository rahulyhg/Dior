<?php
class wmsmgr_ctl_admin_wms extends desktop_controller {

    var $workground = "wms_manager";

    function index(){
        $params = array(
            'title' => 'WMS管理',
            'actions' => array(
                    'add' => array(
                        'label' => '添加WMS',
                        'href' => 'index.php?app=wmsmgr&ctl=admin_wms&act=add',
                        'target' => "dialog::{width:600,height:300,title:'第三方仓储'}",
                    ),
                    array('label'=>'查看绑定关系','href'=>'index.php?app=wmsmgr&ctl=admin_wms&act=view_bindrelation','target'=>'_blank'),
                    array('label'=>app::get('channel')->_('删除'), 'confirm' =>'确定删除选中项？','submit'=>'index.php?app=wmsmgr&ctl=admin_wms&act=deleteChannel',),
            ),
            'base_filter' => array('channel_type'=>'wms'),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
        );
        $this->finder('wmsmgr_mdl_wms', $params);
    }


    function add(){
        $this->_edit();
    }

    function edit($wms_id){
        $this->_edit($wms_id);
    }

    private function _edit($wms_id=NULL){
        if($wms_id){
            $oWms = &$this->app->model('wms');
            $wms_detail = $oWms->dump($wms_id);
            $wms_detail['adapter'] = kernel::single('wmsmgr_func')->getAdapterByChannelId($wms_id);
            $wms_detail['api_url'] = app::get('wmsmgr')->getConf('api_url'.$wms_detail['node_id']);
            $this->pagedata['wms'] = $wms_detail;
        }

        #适配器列表
        // $adapter_list = kernel::single('wmsmgr_func')->getWmsAdapterList();
        
        // $this->pagedata['adapter_list'] = $adapter_list;
        // $this->pagedata['adapter_list_json'] = json_encode($adapter_list);

        $adapter_list = kernel::single('wmsmgr_auth_config')->getAdapterList();
        $this->pagedata['adapter_list'] = $adapter_list;

        $this->display("add_wms.html");
    }

    public function confightml($wms_id,$adapter)
    {
        switch ($adapter) {
            case 'openapiwms':
                if ($wms_id){
                    $oWms = &$this->app->model('wms');
                    $wms_detail = $oWms->dump($wms_id,'node_type,node_id');
                    $this->pagedata['config']['node_type'] = $wms_detail['node_type'];
                    $this->pagedata['wms_id'] = $wms_id;
                }
                break;
            case 'ilcwms':
                if ($wms_id) {
                    $oWms = &$this->app->model('wms');
                    $wms_detail = $oWms->dump($wms_id,'node_type,node_id');
                    // $this->pagedata['config']['node_id'] = $wms_detail['node_id'];
                    // $this->pagedata['wms_id'] = $wms_id;
                    $channel_adapter = app::get('channel')->model('adapter')->dump(array('channel_id'=>$wms_id));
                    $config = @unserialize($channel_adapter['config']);

                    if (!$config['node_id']) $config['node_id'] = $wms_detail['node_id'];
                    if (!$config['url'])     $config['url'] = app::get('wmsmgr')->getConf('api_url'.$wms_detail['node_id']);

                    $this->pagedata['config'] = $config;
                }
                break;
            default:
                # code...
                break;
        }


        $platform_list = kernel::single('wmsmgr_auth_config')->getPlatformList($adapter);

        $this->pagedata['platform_list'] = $platform_list;
        $this->display('auth/'.$adapter.'.html');
    }


    public function platformconfig($wms_id,$platform)
    {
        if ($wms_id) {
            $adapter = app::get('channel')->model('adapter')->dump(array('channel_id'=>$wms_id));
            $config = @unserialize($adapter['config']);
            if ($platform == $config['node_type'])  $this->pagedata['config'] = $config;
        }

        $platform_params = kernel::single('wmsmgr_auth_config')->getPlatformParam($platform);

        $this->pagedata['platform_params'] = $platform_params;
        $this->pagedata['platform']        = $platform;

        $this->display('auth/platformconfig.html');
    }

    function saveWms(){
        $oWms = &$this->app->model("wms");

        $url = 'index.php?app=wmsmgr&ctl=admin_wms&act=index';
        $this->begin($url);
        // $save_data = $_POST['wms'];
        // $api_url = isset($save_data['api_url']) ? $save_data['api_url'] : '';

        // if(empty($save_data['channel_id'])){
        // }

        // if($save_data['adapter'] == 'selfwms'){
            // $save_data['node_id'] = $save_data['node_type'] = 'selfwms';
        // }
        $wms = array('channel_name' => $_POST['wms']['channel_name'], 'channel_type'=>'wms');
        if ($_POST['wms']['channel_id']) $wms['channel_id'] = $_POST['wms']['channel_id'];
        if ($_POST['wms']['channel_bn']) $wms['channel_bn'] = $_POST['wms']['channel_bn'];
        switch ($_POST['wms']['adapter']) {
            case 'selfwms':
                $wms['node_id'] = 'selfwms';
                $wms['node_type'] = 'selfwms';
                break;
            case 'openapiwms':
                $wms['node_type'] = $_POST['config']['node_type'];
                $wms['node_id'] = sprintf('o%u',crc32(utils::array_md5($_POST['config']).kernel::base_url() ));

                if (!$wms['node_type']) {
                    $this->end(false,app::get('base')->_('请选择仓储平台'));
                }

                // 验证node_id是否存在
                $valid = true;
                if ($wms['channel_id']) {
                    if ($oWms->dump(array('channel_id|noequal'=>$wms['channel_id'],'node_id'=>$wms['node_id']))) {
                        $valid = false;
                    }
                } else {
                    if ($oWms->dump(array('node_id'=>$wms['node_id']))) {
                        $valid = false;
                    }
                }

                if ($valid == false) {
                    $this->end(false,app::get('base')->_('node_id重复，请更换秘钥，同时通知仓储更改秘钥'));
                }

                // 判断参数是否都填了
                $params = kernel::single('wmsmgr_auth_config')->getPlatformParam($wms['node_type']);
                if ($params) {
                    foreach ($params as $key => $label) {
                        if (!$_POST['config'][$key]) {
                            $this->end(false,$label.'不能为空');
                        }
                    }
                }

                break;
            case 'ilcwms':
                $wms['node_id'] = $_POST['config']['node_id'];
                break;
            default:
                # code...
                break;
        }


        if($oWms->dump(array('channel_bn'=>$save_data['channel_bn']))){
            $this->end(false,app::get('base')->_('第三方仓储编码重复'));
        }else{

            // $save_data['channel_type'] = 'wms';
            if($rt = $oWms->save($wms)){
                // kernel::single('wmsmgr_func')->saveChannelAdapter($save_data['channel_id'],$save_data['adapter']);
                $adapter = array('channel_id'=>$wms['channel_id'], 'adapter'=>$_POST['wms']['adapter'], 'config'=>serialize($_POST['config']));
                app::get('channel')->model('adapter')->save($adapter);
            }

            #存储节点通信api地址
            // if($api_url){
            //     app::get('wmsmgr')->setConf('api_url'.$save_data['node_id'],$api_url);
            // }

            $rt = $rt ? true : false;
            $this->end($rt,app::get('base')->_($rt?'保存成功':'保存失败'));
        }
    }

    public function deleteChannel(){
        $this->begin('index.php?app=wmsmgr&ctl=admin_wms&act=index');
        $obj_channel = kernel::single('channel_channel');
        $obj_branch = kernel::single('ome_branch');
        $channel_id = $_POST['channel_id'];
        #获取所有已经绑定的应用channel_id
        $_bind_info = $obj_channel->getList(array('channel_id'=>$channel_id),'*');
        $_branch_bind_wms_info = $obj_branch->getBindWmsBranchList();
        $bind_channel_id = array(); #所有已经绑定的channel_id
        $bind_wms_id = array();
        $channel_name = array();

        if(!empty($_bind_info)){
            foreach($_bind_info as $v){
                $channel_name[$v['channel_id']] = $v['channel_name'];

                #把已经存在的绑定节点存起来
                if(strlen($v['node_id'])>0 && $v['node_id'] != 'selfwms'){
                    $bind_channel_id[]= $v['channel_id'];
                }
            }
        }

        if(!empty($_branch_bind_wms_info)){
            foreach($_branch_bind_wms_info as $v){
                if(in_array($v['wms_id'],$channel_id)){
                    $this->end(false,$this->app->_('第三方仓储:'.$channel_name[$v['wms_id']].'，已经绑定了仓库，请先解除绑定!'));
                    exit;
                }
            }
        }

        #验证即将被删除的这条channel_id是否已经解除绑定
        foreach($channel_id as $id){
            $result = array_search($id, $bind_channel_id);
            if($result !== false){
                $this->end(false,$this->app->_('删除前，请解除绑定!'));
                exit;
            }
        }

        #验证完毕，开始删除
        if($obj_channel->delete(array('channel_id'=>$channel_id))){
            $this->end(true, $this->app->_('删除成功'));
        }else{
            $this->end(false, $this->app->_('删除失败'));
        }
    }

    /**
     * 申请绑定关系
     * @param string $app_id
     * @param string $callback 异步返回地址
     * @param string $api_url API通信地址
     */
    function apply_bindrelation($app_id='ome', $callback='', $api_url=''){
        $this->Certi = base_certificate::get('certificate_id');
        $this->Token = base_certificate::get('token');
        $this->Node_id = base_shopnode::node_id($app_id);
        $token = $this->Token;
        $sess_id = kernel::single('base_session')->sess_id();
        $apply['certi_id'] = $this->Certi;
        if ($this->Node_id)
            $apply['node_id'] = $this->Node_id;
        $apply['sess_id'] = $sess_id;
        $str   = '';
        ksort($apply);
        foreach($apply as $key => $value){
            $str.=$value;
        }
        $apply['certi_ac'] = md5($str.$token);

        $params = array(
            'source'    => 'apply',
            'certi_id'  => $apply['certi_id'],
            'node_id'   => $apply['node_id'],
            'sess_id'   => $apply['sess_id'],
            'certi_ac'  => $apply['certi_ac'],
            'callback'  => $callback,
            'api_url'   => $api_url,
            'show_type' => 'wms',
        );

        $this->pagedata['license_iframe'] = sprintf('<iframe width="100%%" frameborder="0" height="99%%" id="iframe" onload="this.height=document.documentElement.clientHeight-4" src="%s" ></iframe>',MATRIX_RELATION_URL . '?' . http_build_query($params));
        // $this->pagedata['license_iframe'] = '<iframe width="100%" frameborder="0" height="99%" id="iframe" onload="this.height=document.documentElement.clientHeight-4" src="' . MATRIX_RELATION_URL . '?source=apply&certi_id='.$apply['certi_id'].'&node_id=' . $apply['node_id'] . '&sess_id='.$apply['sess_id'].'&certi_ac='.$apply['certi_ac'].'&callback=' . $callback . '&api_url=' . $api_url .'" ></iframe>';

        $this->display('bindRelation.html');
    }

    /*
     * 查看绑定关系
     */
    function view_bindrelation(){

        $this->Certi = base_certificate::get('certificate_id');
        $this->Token = base_certificate::get('token');
        //$this->Token = base_shopnode::get('token','ome');
        $this->Node_id = base_shopnode::node_id('ome');
        $token = $this->Token;
        $sess_id = kernel::single('base_session')->sess_id();
        $apply['certi_id'] = $this->Certi;
        $apply['node_id'] = $this->Node_id;
        $apply['sess_id'] = $sess_id;
        $str   = '';
        ksort($apply);
        foreach($apply as $key => $value){
            $str.=$value;
        }
        $apply['certi_ac'] = md5($str.$token);
        $callback = urlencode(kernel::openapi_url('openapi.channel','bindCallback'));
        $api_url = kernel::base_url(true).kernel::url_prefix().'/api';
        $api_url = urlencode($api_url);
        $op_id = kernel::single('desktop_user')->get_login_name();
        $op_user = kernel::single('desktop_user')->get_name();
        // $params = '&op_id='.$op_id.'&op_user='.$op_user;

        $params = array(
            'op_id'     => $op_id,
            'op_user'   => $op_user,
            'source'    => 'accept',
            'certi_id'  => $apply['certi_id'],
            'node_id'   => $this->Node_id,
            'sess_id'   => $apply['sess_id'],
            'certi_ac'  => $apply['certi_ac'],
            'callback'  => $callback,
            'api_url'   => $api_url,
            'show_type' => 'wms',
        );

        // echo '<title>查看绑定关系</title><iframe width="100%" height="95%" frameborder="0" src='.MATRIX_RELATION_URL.'?source=accept&certi_id='.$apply['certi_id'].'&node_id=' . $this->Node_id . '&sess_id='.$apply['sess_id'].'&certi_ac='.$apply['certi_ac'].'&callback='.$callback.'&api_url='.$api_url.$params.' ></iframe>';

        echo sprintf('<title>查看绑定关系</title><iframe width="100%%" height="95%%" frameborder="0" src="%s" ></iframe>',MATRIX_RELATION_URL . '?' . http_build_query($params));
    }

    /**
     * 配置物流公司
     * @param string $wms_id
     */
    public function exitExpress($wms_id = ''){
        $express_relation_mdl = $this->app->model('express_relation');
        if($_POST){
            $this->begin();
            $wms_id = $_POST['wms_id'];
            $wms_express_bn = $_POST['wms_express_bn'];
            foreach($wms_express_bn as $k=>$v){
                $sdata = array('wms_id'=>$wms_id,'sys_express_bn'=>$k,'wms_express_bn'=>$v);
                $rs = $express_relation_mdl->save($sdata);
            }
            $this->end(true,app::get('base')->_($rs?'保存成功':'保存失败'));
        }else{
            $dly_corp_mdl = app::get('ome')->model('dly_corp');
            $sys_express_corp = $dly_corp_mdl->getlist('*');
            $wms = $express_relation_mdl->getlist('*',array('wms_id'=>$wms_id));
            foreach($wms as $v){
                $wmsBn[$v['sys_express_bn']] = $v['wms_express_bn'];
            }
            $this->pagedata['sys_express_corp'] = $sys_express_corp;
            $this->pagedata['wms_id'] = $wms_id;
            $this->pagedata['wmsBn'] = $wmsBn;
            $this->display("exitExpress.html");
        }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function gen_private_key()
    {
        echo md5(uniqid());exit;
    }

}