<?php
class ome_rpc_response_saasmanager_system
{

    function do_chang_pwd($data,& $apiObj){
        $db = kernel::database();
        $op = $db->selectrow('select account_id from sdb_pam_account where login_name = "'.$data['login_name'].'"');
        if($op){
            $db->exec('update sdb_pam_account set login_password = "'.md5($data['login_password']).'" where account_id='.$op['account_id']);
            $apiObj->api_response('已重置');
        }else{
            $apiObj->api_response('没有此管理员');
        }

    }
    /**
     * 提供给套件中心获取管理员信息
     *
     * @param  void
     * @return void
     * @author
     **/
    public function get_admin_info($data, &$apiObj)
    {
        $db = kernel::database();
        $sql = "SELECT a.login_name, a.login_password, u.name FROM sdb_pam_account AS a LEFT JOIN sdb_desktop_users AS u ON a.account_id = u.user_id WHERE a.account_type ='shopadmin'";
        $admin_info = $db->select($sql);
        if ($admin_info) {
            $is_install = app::get('suitclient')->is_installed();
            if(!$is_install){
                kernel::single('base_application_manage')->install('suitclient');
            }
            $msg = $admin_info;
        }else{
            $msg = '缺少管理员';
        }
        $res = array(
                'result'=>'succ',
                'msg' => $msg,
            );

        echo json_encode($res);
        exit;
    }

    /**
     *
     * 刷新数据库kv存储信息
     * @param array $data
     * @param obj $apiObj
     */
    public function flush_db_kvinfo($data,& $apiObj){
        $server_name = strtolower($_SERVER['SERVER_NAME']);
        if(function_exists("memcache_connect")){
            $tt_obj = memcache_connect(SERVER_TT_HOST, SERVER_TT_PORT);
            $preFix = md5(md5(sprintf('%s_%s', $server_name, SERVICE_IDENT)));

            // $saas = fetchHostByDomain($server_name);
            $saasrequest = new saasRequest();
            $saas = $saasrequest->getInfoByHost($server_name);
            
            if (is_object($saas)) {

                $data= array(
                    'HOST_ID'=> $saas->host_id,
                    'DB_USER'=> $saas->db_user,
                    'DB_PASSWORD'=>$saas->db_passwd,
                    'DB_NAME'=>$saas->db_name,
                    'DB_HOST'=>$saas->db_host.":".$saas->db_port,
                    'STORE_KEY'=>md5($server_name),
                    'NICK_NAME'=>$saas->db_user,
                    'STATUS'=>$saas->status,
                    'END_TIME'=>strtotime($saas->cycle_end),
                );
                unset($saas);
            }

            if (empty($data)) {
                //没有开通
                $apiObj->error_handle('没有获取到主机的相关信息不能刷新');
            } elseif ($data['STATUS'] <> 'HOST_STATUS_ACTIVE') {
                //已经开通，还没有激活
                $apiObj->error_handle('主机状态不为活动中不能刷新');
            } elseif (time() > ($data['END_TIME']+86400)) {
                //已经过期
                $apiObj->error_handle('主机已过期不能刷新');
            } else {
                $tt_obj->set($preFix, serialize($data));
                $apiObj->api_response('刷新成功');
            }
        }else{
            $apiObj->error_handle('没有开启持久化存储不用刷新');
        }
    }

    /**
     *
     * 设置当前站点状态
     * @param array $data
     * @param obj $apiObj
     */
    public function set_site_status($data,& $apiObj){
        if(isset($data['status_type']) && isset($data['status_value']) && $data['status_type'] && $data['status_value']){
            $status_type = $data['status_type'];
            $status_value = $data['status_value'];
            $server_name = strtolower($_SERVER['SERVER_NAME']);
            if(function_exists("memcache_connect")){
                $tt_obj = memcache_connect(SERVER_TT_HOST, SERVER_TT_PORT);
                $preFix = md5(md5(sprintf('%s_%s', $server_name, SITE_STATUS_IDENT)));

                $data = unserialize(memcache_get($tt_obj, $preFix));
                $data[$status_type] = $status_value;
                $tt_obj->set($preFix, serialize($data));
                $apiObj->api_response('设置成功');
            }else{
                $apiObj->error_handle('没有开启持久化存储无法设置');
            }
        }else{
            $apiObj->error_handle('缺少必要参数');
        }
    }


    /**
     * 站点版本设置
     * @param array $data
     * @param obj $apiObj 
     **/
    public function set_site_version($data,&$apiObj)
    {
        if(isset($data['version_code']) && $data['version_code']){
            $operation = 'update';
            $server_name = strtolower($_SERVER['SERVER_NAME']);
            // $saas = fetchHostByDomain($server_name);
            $saasrequest = new saasRequest();
            $saas = $saasrequest->getInfoByHost($server_name);

            if (is_object($saas)) {
                $params = array('release_version'=>$data['version_code'],'domain'=>$server_name,'host_id'=>$saas->host_id,'order_id'=>$saas->order_id);
                unset($saas);
            }else{
                $apiObj->error_handle('获取不到站点信息');
            }
            
            if(kernel::single('ome_tgservice_updatescript')->exec_command($operation,$params,$msg)){
                $apiObj->api_response('设置成功');
            }else{
                $apiObj->error_handle($msg);
            }
        }else{
            $apiObj->error_handle('缺少必要参数');
        }
    }

}
