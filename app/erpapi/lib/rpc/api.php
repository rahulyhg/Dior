<?php
class erpapi_rpc_api{
	//private $format = 'json';
	public function process($path){
	 
		if(!kernel::is_online()){
            die('error');
        }else{
            require(ROOT_DIR.'/config/config.php');
            @include(APP_DIR.'/base/defined.php');
        }
		
		//$this->formatObj = kernel::single('erpapi_format_'.$this->format);
		
		set_error_handler(array(&$this,'error_handle'),E_ERROR);
        set_error_handler(array(&$this,'user_error_handle'),E_USER_ERROR);
		
		try {
			list($channel_type, $business, $method) = explode('.',$_POST['method']);
			$objClass=kernel::single('erpapi_oms_'.$business);
			$result=$objClass->{$method}($_POST);
			echo json_encode($result);exit();
		 	//print_r($result);exit();
		
		}catch (Exception $e) {
            trigger_error($e->getMessage(),E_USER_ERROR);
        }
    }
	
	
	function error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    function user_error_handle($error_code, $error_msg){
        $this->send_user_error('4007', $error_msg);
    }

    public function send_user_error($code, $err_msg)
    {
         
        $res = array(
            'rsp'      => 'fail',
            'data'     => '',
            'msg'      => $err_msg,
            'msg_code' => $code, 
        );
        
		echo json_encode($res);exit();
       // echo $this->formatObj->data_encode($res);
        exit;
    }

}