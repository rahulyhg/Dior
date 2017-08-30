<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class base_enterprise{
	static $enterp;
	static $version;
	static $token;
	
	/**
	 * ���ð汾��
	 * @param string �汾��
	 * @return null
	 */
	static function set_version($version='1.0'){
		self::$version = $version;
	}
	
	/**
	 * ����token
	 * @param string token˽Կ
	 * @return null
	 */
	static function set_token($token='962d93c3702255fc40cbcc6fe766c6a8'){
		self::$token = $token;
	}
	
	/**
	 * ��֤��ҵ�ʺź������Ƿ�Ϸ�
	 * @param string format �ӿڲ�����֤��ʽ
	 * @param string ��ҵ�ʺ�
	 * @return boolean true or false
	 */
	static function is_valid($format='json',$enterprise_account=''){
		if (!$enterprise_account) return false;
		
		$data = array(
            'certi_app'=>'ent.check',
            'identifier' => $enterprise_account,
            'version'=> self::$version,
			'format'=>$format,
        );
		ksort($data);
        foreach($data as $key => $value){
            $str.=$value;
        }
        $data['certi_ac'] = md5($str.self::$token);
		$http = kernel::single('base_httpclient');
		$http->timeout = 6;
        $result = $http->post(
            SHOP_USER_ENTERPRISE_API,
            $data);
		
		$tmp_res = json_decode($result, 1);
		if ($tmp_res['res'] == 'succ') return true;
		else return false;
	}
	
	/**
	 * �洢��ҵ�ʺź���Ϣ
	 * @param mixed - ��ҵ�ʺ���Ϣ
	 * @return boolean true or false
	 */
	static function set_enterprise_info($arr_enterprise){
		if(!function_exists('set_certificate')){
			app::get('base')->setConf('ecos.enterprise_info', serialize($arr_enterprise));
            return true;
        }else{
            return set_certificate($arr_enterprise);
        }		
	}
	
	/**
	 * ��ȡ��ҵ��Ϣ
	 * @param string ��ȡ����Ϣ����
	 * @return string ��Ӧ������
	 */
	static function get($code='ent_id'){        
        if(!function_exists('get_ent_id')){
            if(self::$enterp===null){
                if($serialize_enterp = app::get('base')->getConf('ecos.enterprise_info')){
                    $enterprise = unserialize($serialize_enterp);
                    self::$enterp = $enterprise;
                }
            }
        }else{
            self::$enterp = get_ent_id();
        }
        
        return self::$enterp[$code];
    }
	
	/**
	 * ������ҵ��
	 * @param null
	 * @return string
	 */
	static function ent_id() { return self::get('ent_id'); }
	
	/**
	 * ������ҵ����
	 * @param null
	 * @return string
	 */
	static function ent_ac() { return self::get('ent_ac'); }
	
	/**
	 * ������ҵ�ʼ�
	 * @param null
	 * @return string
	 */
	static function ent_email() { return self::get('ent_email'); }
}