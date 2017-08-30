<?php
class taoexlib_ctl_ietask extends desktop_controller{
    
    function index(){
		
    	kernel::single('taoexlib_ietask')->clean();//清除到期下载任务
		
    	$base_filter = array();
    	$is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
        	$base_filter['op_id'] = kernel::single('desktop_user')->get_id();
        }
    	$this->finder(
	        'taoexlib_mdl_ietask',
	        array(
		        'title'=>'导出任务列表',
				'base_filter'=>$base_filter,
				'use_buildin_export'=>false,
		        'use_buildin_set_tag'=>false,
		        'use_buildin_filter'=>true,
		        'use_buildin_tagedit'=>true,
	        	'allow_detail_popup'=>false,
				'use_view_tab'=>false,
	        	'use_buildin_recycle'=>true,
                'orderBy'=>' task_id desc ',
	       	   // 'use_buildin_recycle'=>false,
	        	//'actions' => array(
               //array('label'=>'删除','submit'=>'index.php?app=taoexlib&ctl=ietask&act=delete'),
           		//),
	        )
        );
    }

    function download() {
        $id=$_REQUEST['id'];
        $file_obj = app::get("base")->model("files");
            $t_d = $file_obj->dump(array('file_id'=>$id));
            $ident = $t_d['file_path'];
        list($ret['url'],$ret['id'],$ret['storager']) = explode('|',$ident);
        $filename = ROOT_DIR . '/' . $ret['url'];
        $file_size = filesize($filename);
        $file_name = basename($filename); 
        header("Content-type:text/html;charset=utf-8"); 
        Header("Content-type: application/octet-stream"); 
        Header("Accept-Ranges: bytes"); 
        Header("Accept-Length:".$file_size); 
        Header("Content-Disposition: attachment; filename=".$file_name); 
        echo file_get_contents($filename);
    }

    function newdownload($id){
        $ietaskObj = app::get("taoexlib")->model("ietask");
        $task_info = $ietaskObj->dump(array('task_id'=>$id),'file_name,task_name');

        $storageLib = kernel::single('taskmgr_interface_storage');
        $local_file = DATA_DIR.'/export/tmp_local/'.md5(microtime().KV_PREFIX).$id.'.csv';
        $getfile_res = $storageLib->get($task_info['file_name'],$local_file);
        if($getfile_res){
            $file_size = filesize($local_file);
            //$file_name = basename($local_file);
            $file_name = $task_info['task_name']."-".$id.".csv";

            header("Content-type:text/html;charset=utf-8"); 
            Header("Content-type: application/octet-stream"); 
            Header("Accept-Ranges: bytes"); 
            Header("Accept-Length:".$file_size); 
            Header("Content-Disposition: attachment; filename=".$file_name); 
            echo file_get_contents($local_file);

            @unlink($local_file);
        }
    }

    function import(){
		
    	$base_filter = array();
    	$is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super){
        	$base_filter['op_id'] = kernel::single('desktop_user')->get_id();
        }
        
    	$this->finder(
	        'taoexlib_mdl_ietask_import',
	        array(
		        'title'=>'导入任务列表',
				'base_filter'=>$base_filter,
				'use_buildin_export'=>false,
		        'use_buildin_set_tag'=>false,
		        'use_buildin_filter'=>true,
		        'use_buildin_tagedit'=>true,
	        	'allow_detail_popup'=>false,
				'use_view_tab'=>false,
	        	'use_buildin_recycle'=>true,
                'orderBy'=>' task_id desc ',
	       	   // 'use_buildin_recycle'=>false,
	        	//'actions' => array(
                  // array('label'=>'导入','href'=>'index.php?app=taoexlib&ctl=ietask&act=toImport'),
           		//),
	        )
        );
    }
    
  	function toImport(){      
        $oIeCfg = &$this->app->model('ietask_cfg');
		$cfgList = $oIeCfg->getCfgList($ietask_cfg_id,array('ietask_cfg_id','name'));
        $this->pagedata['cfgList'] = $cfgList;
        echo $this->page('ietask/import.html');
    }
    
    function doImport(){

        if( !$_FILES['import_file']['name'] ){
            echo '<script>top.MessageBox.error("上传失败");alert("未上传文件");</script>';
            exit;
        }
        $oQueue = app::get('base')->model('queue');
        $tmpFileHandle = fopen( $_FILES['import_file']['tmp_name'],"r" );
       
        $mdl = substr($this->object_name,strlen( $this->app->app_id.'_mdl_'));

        $oIo = kernel::servicelist('desktop_io');
        foreach( $oIo as $aIo ){
            if( $aIo->io_type_name == substr($_FILES['import_file']['name'],-3 ) ){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);
        if( !$oImportType ){
            echo '<script>top.MessageBox.error("上传失败");alert("导入格式不正确");</script>';
            exit;
        }
        
        $contents = array();
        $oImportType->fgethandle($tmpFileHandle,$contents);
        $newFileName = $this->app->app_id.'_'.$mdl.'_'.$_FILES['import_file']['name'].'-'.time();
 
        base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName,serialize($contents));
        base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName.'_sdf',serialize(array()));
        base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName.'_error',serialize(array()));

 //       base_kvstore::instance($this->app->app_id.'_'.$mdl)->store($newFileName.'_msg',serialize(array()));

        fclose($tmpFileHandle);
 
        $oo = kernel::single('desktop_finder_builder_to_run_import');
        $msgList = $oo->turn_to_sdf($aaa,array(
                    'file_type' => substr( $_FILES['import_file']['name'],-3 ),
                    'app' => $this->app->app_id,
                    'mdl' => $mdl,
                    'file_name' => $newFileName
                ));
        $msg = array();
        if( $msgList['error'] )
            $rs = array('failure',$msgList['error']);
        else
            $rs = array('success',$msgList['warning']);
        /*
            $queueData = array(
                'queue_title'=>$mdl.app::get('desktop')->_('转sdf'),
                'start_time'=>time(),
                'params'=>array(
                    'file_type' => substr( $_FILES['import_file']['name'],-3 ),
                    'app' => $this->app->app_id,
                    'mdl' => $mdl,
                    'file_name' => $newFileName
                ),
                'worker'=>'desktop_finder_builder_to_run_import.turn_to_sdf',
            );
            $oQueue->save($queueData); 
         */


        $echoMsg = '';
        if( $rs[0] == 'success' ){
			$o = app::get( $this->app->app_id )->model($mdl);
			$oImportType->model = $o;
			$oImportType->finish_import();

            $echoMsg =app::get('desktop')->_('上传成功 已加入队列 系统会自动跑完队列');
            if($msgList['warning']){
                $echoMsg .= app::get('desktop')->_('但是存在以下问题')."\\n";
                $echoMsg .= implode("\\n",$msgList['warning']);
            }
        }else{
            $echoMsg = app::get('desktop')->_('导入失败 错误如下：'."\\n".implode("\\n",$msgList['error']));
        }

        header("content-type:text/html; charset=utf-8");        
        echo "<script>top.MessageBox.success(\"上传成功\");alert(\"".$echoMsg."\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
    }
    
    
    function test(){
    	$a = '<cfg>
  <order_bn>订单号</order_bn>
  <payinfo-pay_name>支付方式</payinfo-pay_name>
  <createtime>下单时间</createtime>
  <shipping-shipping_name>配送方式</shipping-shipping_name>
  <shipping-cost_shipping>配送费用</shipping-cost_shipping>
  <shop_id>来源店铺编号</shop_id>
  <custom_mark>订单附言</custom_mark>
  <consignee-name>收货人姓名</consignee-name>
  <consignee-area-province>收货地址省份</consignee-area-province>
  <consignee-area-city>收货地址城市</consignee-area-city>
  <consignee-area-county>收货地址区/县</consignee-area-county>
  <consignee-addr>收货详细地址</consignee-addr>
  <consignee-telephone>收货人固定电话</consignee-telephone>
  <consignee-email>电子邮箱</consignee-email>
  <consignee-mobile>收货人移动电话</consignee-mobile>
  <consignee-zip>邮编</consignee-zip>
  <shipping-is_cod>货到付款</shipping-is_cod>
  <is_tax>是否开发票</is_tax>
  <tax_title>发票抬头</tax_title>
  <cost_tax>发票金额</cost_tax>
  <order_pmt>优惠方案</order_pmt>
  <pmt_order>订单优惠金额</pmt_order>
  <pmt_goods>商品优惠金额</pmt_goods>
  <discount>折扣</discount>
  <score_g>返点积分</score_g>
  <cost_item>商品总额</cost_item>
  <total_amount>订单总额</total_amount>
  <mode>1</mode>
  <order_item>
    <order_bn>订单号</order_bn>
    <bn>商品货号</bn>
    <name>商品名称</name>
    <unit>购买单位</unit>
    <spec_info>商品规格</spec_info>
    <nums>购买数量</nums>
    <price>商品价格</price>
  </order_item>
</cfg>';
    	//$a2 = array('aa'=>11,'bb'=>22);
		$xml_data = kernel::single('taoexlib_xml')->xml2array($a);
		//$xml_data = kernel::single('taoexlib_xml')->array2xml($a2,'cfg');
		echo '<pre>';var_dump($xml_data);exit;
    }
    
    /*function delete(){
    	var_dump($_POST);exit;
    }*/
    
    /**
     * 保存导出任务到db
     */
    /*public function save_ietask(){
        $http_host = strtolower($_SERVER['HTTP_HOST']);
        $data['app'] = $_POST['app'];
        $data['model'] = $_POST['model'];
        $data['task_name'] = base64_decode($_POST['task_name']).'导出';
        $data['op_name'] = kernel::single('desktop_user')->get_name();
        $data['create_time'] = time();
        $data['file_path'] = substr($http_host,0,strpos($http_host,'.')).'/'.date('Y-m').'/';
        $data['file_name'] = $data['app'].'_'.$data['model'].'_'.time().'.csv';
        $data['status'] = 'running';
        //$data['filter_data'] = $_POST['filter_data'];
        $data['filter_json'] = $_POST['filter_json'];
        $data['total_count'] = 0;
        $data['finish_count'] = 0;
        $data['file_data'] = 'csv:';
        $data['file_data_header'] = 'csv:';

        app::get('taoexlib')->model('ietask')->save($data);
        
    	$flag = true;
        $title = $data['task_name'];
        $worker = 'taoexlib_mdl_ietask@export_id';
        $params = array('task_id'=>$data['task_id']);
        $flag = kernel::single('taoexlib_queue')->setNormalLevel()->create($title,$worker,$params);
        
        if($flag){
        	echo('导入任务提交成功！请稍后到导出任务列表查看导出结果。');
        }else{
        	echo('导入任务提交失败！');
        }
    }*/

    /**
     * ietask导出界面
     */
    /*public function export_task(){
        $finder_id = $_GET['finder_id'];
        $app = $_GET['e_app'];
        $model = $_GET['e_model'];
        $task_name = $_GET['task_name'];
        //$filter_json = '';
        // echo('<pre>');var_dump($_POST);
        // echo('导出任务提交成功，请稍后到导出任务列表查看导出结果。');
        
        //if ($_POST) {
            //$filter_json = json_encode($_POST);
        //}
        
        //$this->pagedata['filter_json'] = $filter_json;
        $this->pagedata['finder_id'] = $finder_id;
        $this->pagedata['app'] = $app;
        $this->pagedata['model'] = $model;
        $this->pagedata['task_name'] = $task_name;
        $this->display("ietask_export.html"); 
    }*/
	
	/**
     * 从数据库读数据生成文件
     */
    /*public function export_csv_file(){
		$task_id = intval($_GET['task_id']);
        $oTaskList = $this->app->model('ietask');
		$file_data = $oTaskList->dump(array('task_id'=>$task_id),'file_name,file_data,file_data_header');
		if($file_data['file_data_header'] != 'csv:') {
			$file_data['file_data'] = $file_data['file_data_header'].$file_data['file_data'];
		}
		$file_data['file_data'] = str_replace('csv:','',$file_data['file_data']);
		header("Content-type:application/vnd.ms-excel");
		header("content-Disposition:filename=".$file_data['file_name']);
		ob_flush();
		if(function_exists('iconv')){
			//excel 2007 读取utf8乱码bug。
			$file_data['file_data'] = iconv('UTF-8', 'GB2312//IGNORE', $file_data['file_data']);
		}else{
			$file_data['file_data'] = $this->charset->utf2local($file_data['file_data']);
		}
		echo($file_data['file_data']);
    }*/
    
}
