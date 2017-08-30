<?php

class ome_service_login {

    /**
     * 分销王登录回写地址
     * @param Array $msg
     */
    public function b2b_login_error($msg) {
        $server_name = trim($_SERVER['SERVER_NAME']);
        if(stristr($server_name,B2B_TG_URL)) {
            $arr = array('msg'=>$msg,'url'=>$server_name);
            $arr = json_encode($arr);
            $arr = base64_encode($arr);
            header('location: '.B2B_API_URL.'?act=loginFail&msg='.$arr);
            exit;
        }
    }

    public function signErrorReturn($params) {

        if ($params['visitor_role'] == 'taobao') {

            header("location: http://fuwu.taobao.com/service/my_service.htm");
            exit;
        } else {

            return false;
        }
    }

    public function realLogin($params, $type) {

        // 如果是分销王登录
        if($params['login_from']=='b2b') {
            $account_id = $this->check_name($params['visitor_nick'],$params['visitor_pwd']);
            if (!$account_id) {
                $this->b2b_login_error('帐号或密码错误');
                return false;
            }
        }else{
        $account_id = $this->check_name($params['visitor_nick']);

        if (!$account_id) {

            $account_id = $this->insert_user($params, $type);
        }
        }

        if ($account_id) {

            kernel::single('base_session')->start();
            $_SESSION['account'][$type] = $account_id;
            $_SESSION['login_time'] = time();

            if ($params['visitor_role'] == 'taobao') {

                app::get('omestart')->setConf('tb_session', $params['top_session']);
                app::get('omestart')->setConf('tb_nick', $params['visitor_nick']);
                app::get('omestart')->setConf('tb_uid', $params['visitor_id']);
            }

            $users = app::get('desktop')->model('users');

            $aUser = $users->dump($account_id, '*');
            $sdf['lastlogin'] = $_SESSION['login_time'] ? $_SESSION['login_time'] : time();
            $sdf['logincount'] = $aUser['logincount'] + 1;
            $users->update($sdf, array('user_id' => $account_id));

            return true;
        }

        return false;
    }

    public function check_name($login_name=null,$login_password=null) {

        $account = app::get('pam')->model('account');
        if($login_password!='') {
            $row = $account->getList('account_id', array('login_name' => $login_name,'login_password' => $login_password));
        }else{
        $row = $account->getList('account_id', array('login_name' => $login_name));
        }

        if ($row)
            return $row[0]['account_id'];
        else
            return false;
    }

    public function insert_user($params, $type) {

        if (!$params)
            return false;

        $account = array(
            'pam_account' => array(
                'login_name' => $params['visitor_nick'],
                'login_password' => md5(DB_PASSWORD),
                'account_type' => $type,
            ),
            'name' => $params['visitor_nick'],
            'super' => 1,
            'status' => 1
        );
        if (app::get('desktop')->model('users')->save($account)) {

            return $account['pam_account']['account_id'];
        } else {

            return false;
        }
    }

    public function login($params=null) {
        if (!$params)
            return false;
        $sign = strtoupper(md5(SASS_APP_KEY . $params['saas_params'] . $params['saas_ts'] . SAAS_SECRE_KEY));

        // begin分销王登录
        if($params['login_from']=='b2b') {
            $sign = strtoupper(md5(B2B_APP_KEY . $params['saas_params'] . $params['saas_ts'] . B2B_SECRE_KEY));
        }
        // end

        $saasParams = base64_decode($params['saas_params']);
        $saasParams = @split('&', $saasParams);

        foreach ((array) $saasParams as $param) {

            if (strpos($param, '=') === false) {

                $key = $param;
                $value = '';
            } else {
                $pos = strpos($param, '=');
                $key = substr($param, 0, $pos);
                $value = substr($param, $pos + 1, strlen($param) - $pos);
            }

            $sParams[$key] = $value;
        }

        if ($sign != $params['saas_sign']) {
            //检验不通过
            $this->b2b_login_error('签名错误');
            return $this->signErrorReturn($sParams);
        } else {

            // begin 如果是分销王登录，必须输入帐号和密码
            if($params['login_from']=='b2b') {
                if (trim($sParams['visitor_nick'])=='' || trim($sParams['visitor_pwd'])=='') {
                    $this->b2b_login_error('帐号和密码不能为空');
                    return $this->signErrorReturn($sParams);
                }
                $sParams['login_from']='b2b';
            }
            // end

            if (is_array($saasParams) && !empty($saasParams)) {
                if (abs(time() - $params['saas_ts']) > 86400) {
                    //检查时间，已经过了有效期
                    $this->b2b_login_error('登录超时');
                    return $this->signErrorReturn($sParams);
                } else {

                    if (trim($sParams['server_name']) != trim($_SERVER['SERVER_NAME'])) {
                        $this->b2b_login_error('网址不匹配');
                        return $this->signErrorReturn($sParams);
                    } else {

                        return $this->realLogin($sParams, $params['type']);
                    }
                }
            } else {
                if (trim($sParams['server_name']) != trim($_SERVER['SERVER_NAME'])) {

                    return $this->signErrorReturn($sParams);
                } else {

                    $sParams['visitor_nick'] = 'admin';
                    return $this->realLogin($sParams, $params['type']);
                }
            }
        }
    }

}
