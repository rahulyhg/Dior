<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class pam_account{

    function __construct($type){
        $this->type = $type;
        $this->session = kernel::single('base_session')->start();
    }

    function is_valid(){
        return $_SESSION['account'][$this->type];
    }
    
    function is_exists($login_name){
        if(app::get('pam')->model('account')->getList('account_id',array('account_type'=>$this->type,'login_name'=>$login_name)))
            return true;
        else
            return false;
    }

    function update($module,$module_uid, $auth_data){
        if($module!='pam_passport_basic'){
            $auth_model = app::get('pam')->model('auth');
            if($row = $auth_model->getlist('*',array(
                    'module_uid'=>$module_uid,
                    'module'=>$module,
                ),0,1)){
                $auth_model->update(array('data'=>$auth_data),array(
                    'module_uid'=>$module_uid,
                    'module'=>$module,
                ));
                $account_id = $row[0]['account_id'];
            }else{
                $account = app::get('pam')->model('account');
                $login_name = microtime();
                while($row = $account->getList('account_id',array('login_name' => $login_name,'account_type' => $this->type)))
                {
                	$login_name = microtime();
                }
                $data = array(
                            'login_name' => $login_name,
                            'login_password' => md5(time()),
                            'account_type'=>$this->type,
                            'createtime'=>time(),
                    );
                $account_id = $account->insert($data);
				if(!$account_id) return false;
                $data = array(
                    'account_id'=>$account_id,
                    'module_uid'=>$auth_data['login_name'],
                    'module'=>$module,
                    'data'=>$auth_data,
                );
                $auth_model->insert($data);
            }
        }else{
            $account_id = $module_uid;
        } 

        $_SESSION['account'][$this->type] = $account_id;
        return true;
    }

    static function register_account_type($app_id,$type,$name){
        $account_types = app::get('pam')->getConf('account_type');
        $account_types[$app_id] = array('name' => $name, 'type' => $type);
        app::get('pam')->setConf('account_type',$account_types);
    }

    static function unregister_account_type($app_id){
        $account_types = app::get('pam')->getConf('account_type');
        unset($account_types[$app_id]);
        app::get('pam')->setConf('account_type',$account_types);
    }

    static function get_account_type($app_id = 'b2c') 
    {
        $aType = app::get('pam')->getConf('account_type');
        //todo
        return $aType[$app_id]['type'];
        //return 'member';
    }//End Function
    

}
