<?php
#第三方应用中心
class channel_ctl_admin_channel extends desktop_controller{
    #定义应用类型，该数组的键必须和sdb_ome_channel表channel_type字段保持绝对一致
    var $workground = "channel_center";  
    public  static $appType = array('crm'=>'crm','wwgenius'=>'旺旺精灵');
    
    #查看绑定关系
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
    
        $callback = urlencode(kernel::openapi_url('openapi.channel.crm', 'crm_callback', array()));
        $api_url = kernel::base_url(true) . kernel::url_prefix() . '/api';
        $api_url = urlencode($api_url);
        $op_id = kernel::single('desktop_user')->get_login_name();
        $op_user = kernel::single('desktop_user')->get_name();
        $params = '&op_id=' . $op_id . '&op_user=' . $op_user;
        echo '<title>查看绑定关系</title><iframe width="100%" height="95%" frameborder="0" src=' . MATRIX_RELATION_URL . '?source=accept&api_v='.$api_v.'&certi_id=' . $apply['certi_id'] . '&node_id=' . $this->Node_id . '&sess_id=' . $apply['sess_id'] . '&certi_ac=' . $apply['certi_ac'] . '&callback=' . $callback . '&api_url=' . $api_url . $params . ' ></iframe>';
    }
}