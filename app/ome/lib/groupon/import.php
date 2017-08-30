<?php

/**
 * 导入订单
 *
 * @author shiyao744@sohu.com
 * @version 0.1b
 */
ini_set('memory_limit', '256M');



class ome_groupon_import {
    /**
     * 订单模块APP名
     * @var String
     */

    const __ORDER_APP = 'ome';

  
     /**
     * 插件列表
     * @var Array
     */
    static $_plugObjects = array();
    

    /**
     * 插件组
     */
    private $_plugins = array('speed');

    public function __construct() {


    }

    /**
     * 订单导入处理
     *
     * @param Array $group 订单组
     * @return Mixed
     * @author sy (2012/01/31)
     */
    public function process($post) {
        $return = $this->_localSaveFile();
        $data = $return['data'];
       	
        if($return['rsp'] == 'fail'){
        	return $return;
        }
        
       	$return = $this->vaild($post);
    	if($return['rsp'] == 'fail'){
        	return $return;
        }
        
        $objPlugin = $this->_instancePlugin($post['pluginId']);
        
        return $objPlugin->process($data,$post);
    }
    
    public function vaild(& $post){
    	$vaild_list = array('pluginId','shop_id','is_pay','groupon_name');
    	foreach($vaild_list as $field){
			if(!isset($post[$field]) || empty($post[$field])){
				return  kernel::single('ome_func')->getErrorApiResponse($field .'为空!');
			}
		}
		
		
		if($post['is_pay'] == 'yes'){
			$vaild_list = array('bank','account','pay_type','pay_account');
			foreach($vaild_list as $field){
				if(!isset($post[$field]) || empty($post[$field])){
					return  kernel::single('ome_func')->getErrorApiResponse($field .'为空!');
				}
			}
		}
    }
    
    public function getPluginList(){
    	$plugins = array();
    	foreach($this->_plugins as $plugin){
    		$objPlugin = $this->_instancePlugin($plugin);
    		$plugins[$plugin] = $objPlugin->getPluginName();
    	}
    	
    	return $plugins;
    }
    
	private function _localSaveFile(){
	   if( !$_FILES['import_file']['name'] ){
	   		return  kernel::single('ome_func')->getErrorApiResponse("未上传文件");
        }
        $tmpFileHandle = fopen( $_FILES['import_file']['tmp_name'],"r" );
       
        $mdl = $_POST['model'];
        $app_id = $_POST['app'];

        $oIo = kernel::servicelist('desktop_io');
        foreach( $oIo as $aIo ){
            if( $aIo->io_type_name == substr($_FILES['import_file']['name'],-3 ) ){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);
        if( !$oImportType ){
        	return  kernel::single('ome_func')->getErrorApiResponse("导入格式不正确");
        }
        
        $contents = array();
        $oImportType->fgethandle($tmpFileHandle,$contents);
       // $newFileName = $app_id.'_'.$mdl.'_'.$_FILES['import_file']['name'].'-'.time();
 
       // base_kvstore::instance($app_id.'_'.$mdl)->store($newFileName,serialize($contents));
       // base_kvstore::instance($app_id.'_'.$mdl)->store($newFileName.'_sdf',serialize(array()));
      //  base_kvstore::instance($app_id.'_'.$mdl)->store($newFileName.'_error',serialize(array()));

        fclose($tmpFileHandle);
        unset($contents[0]);
        
        $tm_contents = array();
        foreach($contents as $row){
        	if(!empty($row[0])){
        		$tm_contents[] = $row;
        	}
        }
        $contents = $tm_contents;
       
        if(empty($contents)){
        	return  kernel::single('ome_func')->getErrorApiResponse("导入数据项为空");
        }else{
        	return  kernel::single('ome_func')->getApiResponse($contents);
        }
	}
	
    /**
     * 通过插件名获取插件类并返回
     *
     * @param String $plugName 插件名
     * @return Object
     */
    private function & _instancePlugin($plugName) {

        $fullPluginName = sprintf('ome_groupon_plugin_%s', $plugName);
        $fix = md5(strtolower($fullPluginName));

        if (!isset(self::$_plugObjects[$fix])) {

            $obj = new $fullPluginName();
            if ($obj instanceof ome_groupon_plugin_interface) {

                self::$_plugObjects[$fix] = $obj;
            }
        }
        return self::$_plugObjects[$fix];
    }
    
 	function exportOrderTemplate(){
 		$arr = array('*:订单号','*:收件人','*:省','*:市','*:区（县）','*:收件人地址','*:手机','*:电话','*:总数','*:快递公司','*:发货时间/备注','*:购买时间','*:sku代码','*:团购价','*:配送费用','*:卖家备注','*:货到付款');
        foreach ($arr as $v){
            $title[] = kernel::single('base_charset')->utf2local($v);
        }
        return $title;
    }
   

  

}