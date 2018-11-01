<?php
define('PHPEXCEL_ROOT', ROOT_DIR.'/app/omecsv/lib/static');
require_once PHPEXCEL_ROOT.'/PHPExcel.php';
require_once PHPEXCEL_ROOT.'/PHPExcel/IOFactory.php';
require_once PHPEXCEL_ROOT.'/PHPExcel/CachedObjectStorageFactory.php';
require_once PHPEXCEL_ROOT.'/PHPExcel/Settings.php';
require_once PHPEXCEL_ROOT.'/PHPExcel/CachedObjectStorage/MemorySerialized.php';
class ome_ctl_admin_statement extends desktop_controller{
	var $name = "对账管理";
    var $workground = "finance_center";

	public function index(){
		switch($_GET['view']){
			case '5':
				$actions = array(
                    array('label'=>'导入账单','href'=>'index.php?app=ome&ctl=admin_statement&act=importBill','target'=>'dialog::{width:400,height:150,title:\'导入\'}'),
					//array('label'=>'同步AX','submit'=>'index.php?app=ome&ctl=admin_statement&act=sync_ax'),
                );
				break;
			case '3':
				$actions = array(
                    array('label'=>'导入账单','href'=>'index.php?app=ome&ctl=admin_statement&act=importBill','target'=>'dialog::{width:400,height:150,title:\'导入\'}'),
					//array('label'=>'同步AX','submit'=>'index.php?app=ome&ctl=admin_statement&act=sync_ax'),
				);
				break;
			case '7':
				$actions = array(
                    array('label'=>'导入账单','href'=>'index.php?app=ome&ctl=admin_statement&act=importBill','target'=>'dialog::{width:400,height:150,title:\'导入\'}'),
					array('label'=>'COD二次导入','href'=>'index.php?app=ome&ctl=admin_statement&act=cod_import','target'=>'dialog::{width:400,height:150,title:\'导入\'}'),
				);
				break;
			case '8'://正常订单走合并流程发给AX
    			$actions = array(
                    array('label'=>'同步AX','confirm'=>app::get('ome')->_('是否确认同步AX'),'submit'=>'index.php?app=ome&ctl=admin_statement&act=sync_ax'),
    			);
    			break;
       		case '9'://款平未发货订单 走挂账流程发给AX
    			$actions = array(
                    array('label'=>'挂账','submit'=>'index.php?app=ome&ctl=admin_statement&act=sync_ax'),
    			);
    			break;
            case '10'://礼品卡兑礼订单走原有流程发给AX
    			$actions = array(
                    array('label'=>'同步AX','confirm'=>app::get('ome')->_('是否确认同步AX'),'submit'=>'index.php?app=ome&ctl=admin_statement&act=sync_ax_giftcard'),
    			);
    			break;
			default:
				$actions = array(
                    array('label'=>'导入账单','href'=>'index.php?app=ome&ctl=admin_statement&act=importBill','target'=>'dialog::{width:400,height:150,title:\'导入\'}'),
					
				);
		
		}

		
		$base_filter = array('disabled'=>'false');
		$params = array(
				'title'=>'收款对账',
				'actions'=>$actions,
				'use_buildin_new_dialog' => false,
				'use_buildin_set_tag'=>false,
				'use_buildin_recycle'=>false,
				'use_buildin_export'=>true,
				'use_buildin_import'=>false,
				'use_buildin_filter'=>true,
				'base_filter' =>$base_filter,
			);
		$this->finder('ome_mdl_statement',$params);
	}

	 function _views(){
		 $objPayment = app::get('ome')->model('statement');
		 $all_count = $objPayment->count();
		 $none_count = $objPayment->count(array('balance_status'=>'none'));
		 $conf_count = $objPayment->count(array('cod_time'=>'second','balance_status'=>array('auto','hand')));
		 $requ_count = $objPayment->count(array('balance_status'=>'require'));
		 $sync_count = $objPayment->count(array('balance_status'=>'sync'));
		 $not_has_count = $objPayment->count(array('balance_status'=>'not_has','disabled'=>'false'));
		 $cod_first = $objPayment->count(array('cod_time'=>'first','disabled'=>'false'));
         $so_succCount = $objPayment->count(array('cod_time'=>'second','so_bn|noequal'=>'','so_status'=>'so_succ','balance_status'=>array('auto','hand')));
         $so_failCount = $objPayment->count(array('cod_time'=>'second','balance_status'=>array('auto','hand'),'filter_sql' => 'so_bn is null'));
         $cardCount = $objPayment->count(array('cod_time'=>'second','shop_id'=>'4395c5a0b113b9d11cb4ba53c48b4d88','balance_status'=>array('auto','hand')));
		 $sub_menu = array(
            1 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'addon'=>$all_count,'optional'=>false),
            2 => array('label'=>app::get('base')->_('未对账'),'filter'=>array('balance_status'=>'none'),'addon'=>$none_count,'optional'=>false),
            
			3 => array('label'=>app::get('base')->_('已对账'),'filter'=>array('cod_time'=>'second','balance_status'=>array('auto','hand')),'addon'=>$conf_count,'optional'=>false),
            4 => array('label'=>app::get('base')->_('人工确认'),'filter'=>array('balance_status'=>'require'),'addon'=>$requ_count,'optional'=>false),
			5 => array('label'=>app::get('base')->_('已同步'),'filter'=>array('balance_status'=>'sync'),'addon'=>$sync_count,'optional'=>false),
			6 => array('label'=>app::get('base')->_('不匹配记录'),'filter'=>array('balance_status'=>'not_has','disabled'=>'false'),'addon'=>$not_has_count,'optional'=>false),
			7 => array('label'=>app::get('base')->_('cod第一次导入'),'filter'=>array('cod_time'=>'first','disabled'=>'false'),'addon'=>$cod_first,'optional'=>false),
			8 => array('label'=>app::get('base')->_('官网货平款平'),'filter'=>array('cod_time'=>'second','so_bn|noequal'=>'','so_status'=>'so_succ','balance_status'=>array('auto','hand')),'addon'=>$so_succCount,'optional'=>false),
            9 => array('label'=>app::get('base')->_('官网款平未发货'),'filter'=>array('cod_time'=>'second','balance_status'=>array('auto','hand'),'filter_sql' => 'so_bn is null'),'addon'=>$so_failCount,'optional'=>false),
            10 => array('label'=>app::get('base')->_('礼品卡兑礼订单'),'filter'=>array('cod_time'=>'second','shop_id'=>'4395c5a0b113b9d11cb4ba53c48b4d88','balance_status'=>array('auto','hand')),'addon'=>$cardCount,'optional'=>false),

        );
//print_r($sub_menu);exit;
		return $sub_menu;
	 }

	 public function importBill(){
		$this->pagedata['finder_id'] = $_GET['finder_id'];
		$this->display('admin/balance/import.html');
	 }

	  public function cod_import(){
		$this->pagedata['finder_id'] = $_GET['finder_id'];
		$this->display('admin/balance/cod_import.html');
	 }


	 public function do_import(){
		 $this->begin('index.php?app=ome&ctl=admin_statement&act=index');
		 @set_time_limit(600);   @ini_set('memory_limit','1024M');
        $fileName = $_FILES['import_file']['name'];
	//	echo "<pre>";print_r($_FILES);exit;
        if( !$fileName ){
			$this->end(false,'上传失败，未上传文件');
        }

		$pathinfo = pathinfo($fileName);

        $oIo = kernel::servicelist('omecsv_io');
        foreach( $oIo as $aIo ){        
            if( $aIo->io_type_name == $pathinfo['extension']){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);

		 if( !$oImportType ){
            echo '<script>top.MessageBox.error("上传失败");alert("导入格式错误");</script>';
            exit;
        }

		try {
            # 条数限制
            $sheetInfo = $oImportType->listWorksheetInfo($_FILES['import_file']['tmp_name']);
            if ((int)$sheetInfo['totalRows'] > $oImportType->limitRow ) {
				$this->end(false,'上传失败，导入数据量过大，请减至'.$oImportType->limitRow.'单以下');
            }

            $contents = array();
            $oImportType->fgethandle($_FILES['import_file']['tmp_name'],$contents);
        } catch (Exception $e) {
            $msg = $e->getMessage();
			$this->end(false,$msg);
        }
	
		$pay_type = explode('_',$pathinfo['filename']);
		$balance_import_account = kernel::single('ome_balance_to_import');
		$balance_import_account->pay_type = $pay_type[0];
		$re = $balance_import_account->do_paymens_bill($contents,$msg);

		if($re){
			header("content-type:text/html; charset=utf-8");
			echo "<script>parent.MessageBox.success(\"上传成功\");alert(\"上传成功\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
			$this->end(true,'操作成功');
		}else{
			$this->end(false,$msg);
		}
		
	 }

	 public function do_cod_import(){
		 $this->begin('index.php?app=ome&ctl=admin_statement&act=index');
		 @set_time_limit(600);   @ini_set('memory_limit','1024M');
        $fileName = $_FILES['import_file']['name'];
	//	echo "<pre>";print_r($_FILES);exit;
        if( !$fileName ){
			$this->end(false,'上传失败，未上传文件');
        }

		$pathinfo = pathinfo($fileName);

        $oIo = kernel::servicelist('omecsv_io');
        foreach( $oIo as $aIo ){        
            if( $aIo->io_type_name == $pathinfo['extension']){
                $oImportType = $aIo;
                break;
            }
        }
        unset($oIo);

		 if( !$oImportType ){
            echo '<script>top.MessageBox.error("上传失败");alert("导入格式错误");</script>';
            exit;
        }

		try {
            # 条数限制
            $sheetInfo = $oImportType->listWorksheetInfo($_FILES['import_file']['tmp_name']);
            if ((int)$sheetInfo['totalRows'] > $oImportType->limitRow ) {
				$this->end(false,'上传失败，导入数据量过大，请减至'.$oImportType->limitRow.'单以下');
            }

            $contents = array();
            $oImportType->fgethandle($_FILES['import_file']['tmp_name'],$contents);
        } catch (Exception $e) {
            $msg = $e->getMessage();
			$this->end(false,$msg);
        }

		$pay_type = explode('_',$pathinfo['filename']);
		if($pay_type[0]!='cod'){
			$this->end(false,'对账文件格式不对请检查');
		}
		$balance_import_account = kernel::single('ome_balance_to_import');
		$balance_import_account->pay_type = 'second_cod';
		$re = $balance_import_account->do_paymens_bill($contents,$msg);

		if($re){
			header("content-type:text/html; charset=utf-8");
			echo "<script>parent.MessageBox.success(\"上传成功\");alert(\"上传成功\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
			$this->end(true,'操作成功');
		}else{
			$this->end(false,$msg);
		}
		
	 }

	 public function  getFilter($filter){
		if($filter['act']=='index'){
			$base_filter = array('cod_time'=>'second','balance_status'=>array('auto','hand'));

			$filter = array_merge($filter,$base_filter);
		}
		return $filter;
	}

	 public function sync_ax(){
		$this->begin('index.php?app=ome&ctl=admin_statement&act=index');
		$paymentObj = app::get('ome')->model('statement');

		if($_POST['isSelectedAll']=='_ALL_'){
			$filter = $this->getFilter($_POST);
			$allInfo = $paymentObj->getList('statement_id',$filter);
			$payment_ids = array();
			foreach($allInfo as $sids){
				$payment_ids[] = $sids['statement_id'];
			}
		}else{
			$payment_ids = $_POST['statement_id'];
		}

		if(empty($payment_ids)){
			$this->end(false,'请选择数据');
		}

		$payments = $paymentObj->getList('*',array('statement_id'=>$payment_ids));
		foreach($payments as $payment){
			if(!in_array($payment['balance_status'],array('auto','hand','sync'))){
				$this->end(false,'存在未完成对账的文件');
			}
		}

		$paymentObj->update(array('balance_status'=>'running'),array('statement_id'=>$payment_ids));
		$this->end(true,'同步完成');

		/*$ax_info = array();
	//	$ax_info[] = array('Paynment date','Customer number','Pay amount','order Amount','Order Number','Reason Code','Paynment method','Brand','Fee amount','Transaction Text');

		$objMath = kernel::single('eccommon_math');
		$objOrder = app::get('ome')->model('orders');
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');

		foreach($payments as $row){
			$arow = array();
			$arow[] = date('d/m/Y',$row['pay_time']);
			$arow[] = $ax_setting['ax_h_customer_account']?$ax_setting['ax_h_customer_account']:'C4010P1';
			if($row['original_type']=='refunds'){
				$arow[] = sprintf("%1\$.2f",-$objMath->number_plus(array(abs($row['tatal_amount']),0)));
				$arow[] = sprintf("%1\$.2f",-abs($objMath->number_plus(array($row['money'],0))));
			}else{
				$arow[] = $objMath->number_plus(array($row['tatal_amount'],0));
				$arow[] = $objMath->number_plus(array($row['money'],0));
			}

			$order_bn = $objOrder->dump($row['order_id'],'order_bn');
			if($row['original_type']=='refunds'){
				$objRefundApply = app::get('ome')->model('refund_apply');
				$refundInfo = $objRefundApply->getList('reship_id',array('refund_apply_bn'=>$row['original_bn']));
				
				$reship_id = $refundInfo[0]['reship_id'];
				if($reship_id){
					$objReship = app::get('ome')->model('reship');
					$allReship = $objReship->getList('reship_id',array('order_id'=>$row['order_id']));
					$reships = array_reverse($allReship);

					foreach($reships as $key=>$value){
						if($reship_id==$value['reship_id']){
							$R = $key;
							break;
						}
					}
					$order_bn['order_bn'] = $order_bn['order_bn'].'-R'.($R+1);
				}else{
					$deliveryInfo = app::get('ome')->model('delivery_order')->getList('*',array('order_id'=>$row['order_id']));
					if($deliveryInfo){
						$order_bn['order_bn'] = $order_bn['order_bn'].'-R1';
					}
					$order_bn['order_bn'] = $order_bn['order_bn'].'-R1';
				}
			}
			$arow[] = $order_bn['order_bn'];
			$arow[] = $row['difference_reason'];
			if($row['paymethod']=='WeiChat'){
				$row['paymethod'] = 'WeChat';
			}
			$arow[] = $row['paymethod'];
			$arow[] = 'PG4A';
			if($row['original_type']=='refunds'){
				$arow[] =sprintf("%1\$.2f",-abs($objMath->number_plus(array($row['pay_fee'],0))));
			}else{
				$arow[] =sprintf("%1\$.2f",abs($objMath->number_plus(array($row['pay_fee'],0))));
			}
			if($row['original_type']=='payments'){
				$arow[] = $row['paymethod'].' payment '.$order_bn['order_bn'].' in '.$arow[0];
			}else{
				$arow[] = $row['paymethod'].' return '.$order_bn['order_bn'].' in '.$arow[0];
			}
			
			$ax_info[] = implode(',',$arow);
		}
		$content = implode("\n",$ax_info);
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
		$file_brand = $ax_setting['ax_file_brand'];
		$file_prefix = $ax_setting['ax_file_prefix'];

		$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));

		$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		
		//echo "<pre>";print_r($file_name);exit;
		$file = fopen($file_name,"w");
		fwrite($file,$content);
		fclose($file);
		//同步AX
		$params['remote'] = basename($file_name);
		$params['local'] = $file_name;
		$params['resume'] = 0;

		$ftp_log_data = array(
				'io_type'=>'out',
				'work_type'=>'payments',
				'createtime'=>time(),
				'status'=>'prepare',
				'file_local_route'=>$params['local'],
				'file_ftp_route'=>$params['remote'],
			);
		$objLog = kernel::single('omeftp_log');
		$ftp_log_id = $objLog->write_log($ftp_log_data,'ftp');

		$ftp_flag = kernel::single('omeftp_ftp_operate')->push($params,$msg);
		if($ftp_flag){
			$objLog->update_log(array('status'=>'succ','lastmodify'=>time(),'memo'=>'上传成功！'),$ftp_log_id,'ftp');
			$paymentObj->update(array('balance_status'=>'sync'),array('statement_id'=>$payment_ids));
		}else{
			$objLog->update_log(array('status'=>'fail','memo'=>$msg),$ftp_log_id,'ftp');
		}
		$this->end(true,'同步完成');*/
		
	 }

	 public function confirm_status($statement_id){
		 $orderObj = app::get('ome')->model('orders');
		 $statementObj = app::get('ome')->model('statement');

		 $payments = $statementObj->dump($statement_id);
		// echo "<pre>";print_r($payments);exit;
		 $filter = array('order_id'=>$payments['order_id']);
		 $order = $orderObj->dump($filter,'order_bn');
		 $payments['order_bn'] = $order['order_bn'];
		 $this->pagedata['payments'] = $payments;
		 $this->page('admin/balance/comfirm_status.html');
	 }

	 public function do_confirm_status(){
		  $this->begin();
		  $paymentObj = app::get('ome')->model('statement');
		  $pData['statement_id'] = $_POST['statement_id'];
		  $pData['difference_reason'] = $_POST['difference_reason'];
		  $pData['balance_status'] = 'hand';

		  $paymentObj->save($pData);
		  $this->end(true,'确认成功');
	 }

	 public function confirm_cancel($statement_id){
		 $statementObj = app::get('ome')->model('statement');
		 $statement = $statementObj->dump($statement_id);

		 $this->pagedata['statement'] = $statement;

		 $this->page('admin/balance/comfirm_cancel.html');
	 }

	 public  function do_confirm_cancel($statement_id){
		 $this->begin();
		 $statementObj = app::get('ome')->model('statement');
		 $pData['statement_id'] = $_POST['statement_id'];
		 $pData['memo'] = $_POST['memo'];
		 $pData['disabled'] = 'true';
		 $statementObj->save($pData);	
		 $this->end(true,'确认成功');
	 }

	 public function sync_cod_ax(){
		$this->begin('index.php?app=ome&ctl=admin_statement&act=index');
		$payment_ids = $_POST['statement_id'];
		$paymentObj = app::get('ome')->model('statement');
		$payments = $paymentObj->getList('*',array('statement_id'=>$payment_ids));
		foreach($payments as $payment){
			if(!in_array($payment['balance_status'],array('auto','hand','sync'))){
				$this->end(false,'存在未完成对账的文件');
			}
		}
		$ax_info = array();
		//$ax_info[] = array('Paynment date','Customer number','Pay amount','order Amount','Order Number','Reason Code','Paynment method','Brand','Fee amount','Transaction Text');
		$objMath = kernel::single('eccommon_math');
		$objOrder = app::get('ome')->model('orders');

		foreach($payments as $row){
			if($row['paymethod']!='cod'){
				continue;
			}
			$arow = array();
			$arow[] = date('d/m/Y',$row['pay_time']);
			$arow[] = 'C4009P1';
			if($row['original_type']=='refunds'){
				$arow[] = -abs($objMath->number_plus(array($row['money'],0)));
				$arow[] = -abs($objMath->number_plus(array($row['tatal_amount'],0)));
			}else{
				$arow[] = $objMath->number_plus(array($row['money'],0));
				$arow[] = $objMath->number_plus(array($row['tatal_amount'],0));
			}

			$order_bn = $objOrder->dump($row['order_id'],'order_bn');
			if($row['original_type']=='refunds'){
				$all_statement = $paymentObj->db->select('select * from sdb_ome_statement where original_type="refunds" and order_id = '.$row['order_id']);
				foreach($all_statement as $key=>$value){
					if($row['statement_id']==$value['statement_id']){
						$R = $key;
						break;
					}
				}
				$order_bn['order_bn'] = $order_bn['order_bn'].'-R'.($R+1);
			}
			$arow[] = $order_bn['order_bn'];
			$arow[] = $row['difference_reason'];
			if($row['paymethod']=='WeiChat'){
				$row['paymethod'] = 'WeChat';
			}
			$arow[] = $row['paymethod'];
			$arow[] = 'PG4A';
			$arow[] = abs($objMath->number_plus(array($row['pay_fee'],0)));
			if($row['original_type']=='payments'){
				$arow[] = $row['paymethod'].' payment '.$order_bn['order_bn'].' in '.$arow[0];
			}else{
				$arow[] = $row['paymethod'].' return '.$order_bn['order_bn'].' in '.$arow[0];
			}
			
			$ax_info[] = implode(',',$arow);
		}
		if(!$ax_info){
			$this->end(false,'所选的数据没有COD对账单，请检查！');
		}
		$content = implode("\n",$ax_info);
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
		$file_brand = $ax_setting['ax_file_brand'];
		$file_prefix = $ax_setting['ax_file_prefix'];

		$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));

		$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		
		//echo "<pre>";print_r($file_name);exit;
		$file = fopen($file_name,"w");
		fwrite($file,$content);
		fclose($file);
		//同步AX
		$params['remote'] = basename($file_name);
		$params['local'] = $file_name;
		$params['resume'] = 0;

		$ftp_log_data = array(
				'io_type'=>'out',
				'work_type'=>'payments',
				'createtime'=>time(),
				'status'=>'prepare',
				'file_local_route'=>$params['local'],
				'file_ftp_route'=>$params['remote'],
			);
		$objLog = kernel::single('omeftp_log');
		$ftp_log_id = $objLog->write_log($ftp_log_data,'ftp');

		$ftp_flag = kernel::single('omeftp_ftp_operate')->push($params,$msg);
		if($ftp_flag){
			$objLog->update_log(array('status'=>'succ','lastmodify'=>time(),'memo'=>'上传成功！'),$ftp_log_id,'ftp');
		}else{
			$objLog->update_log(array('status'=>'fail','memo'=>$msg),$ftp_log_id,'ftp');
		}
		$paymentObj->update(array('balance_status'=>'sync'),array('statement_id'=>$payment_ids));
		$this->end(true,'同步完成');
		
	 }
     //礼品卡账单直接生成paymentfile
     function sync_ax_giftcard(){
        $this->begin('index.php?app=ome&ctl=admin_statement&act=index');
		$payment_ids = $_POST['statement_id'];
		$paymentObj = app::get('ome')->model('statement');
		$payments = $paymentObj->getList('*',array('statement_id'=>$payment_ids));
		foreach($payments as $payment){
			if(!in_array($payment['balance_status'],array('auto','hand','sync'))){
				$this->end(false,'存在未完成对账的文件');
			}
		}
		$ax_info = array();
		//$ax_info[] = array('Paynment date','Customer number','Pay amount','order Amount','Order Number','Reason Code','Paynment method','Brand','Fee amount','Transaction Text');
		$objMath = kernel::single('eccommon_math');
		$objOrder = app::get('ome')->model('orders');
        foreach($payments as $row){
			$payment_ids[] = $row['statement_id'];
			$arow = array();
			$arow[] = date('d/m/Y',$row['pay_time']);
			$arow[] = $ax_setting['ax_h_customer_account']?$ax_setting['ax_h_customer_account']:'C4010P1';
			if($row['original_type']=='refunds'){
				$arow[] = sprintf("%1\$.2f",-$objMath->number_plus(array(abs($row['tatal_amount']),0)));
				$arow[] = sprintf("%1\$.2f",-abs($objMath->number_plus(array($row['money'],0))));
			}else{
				$arow[] = $objMath->number_plus(array($row['tatal_amount'],0));
				$arow[] = $objMath->number_plus(array($row['money'],0));
			}

			$order_bn = $objOrder->dump($row['order_id'],'order_bn');
			if($row['original_type']=='refunds'){
				$objRefundApply = app::get('ome')->model('refund_apply');
				$refundInfo = $objRefundApply->getList('reship_id',array('refund_apply_bn'=>$row['original_bn']));
				
				$reship_id = $refundInfo[0]['reship_id'];
				if($reship_id){
					$objReship = app::get('ome')->model('reship');
					$allReship = $objReship->getList('reship_id',array('order_id'=>$row['order_id']));
					$reships = array_reverse($allReship);

					foreach($reships as $key=>$value){
						if($reship_id==$value['reship_id']){
							$R = $key;
							break;
						}
					}
					$order_bn['order_bn'] = $order_bn['order_bn'].'-R'.($R+1);
				}else{
					$deliveryInfo = app::get('ome')->model('delivery_order')->getList('*',array('order_id'=>$row['order_id']));
					if($deliveryInfo){
						$order_bn['order_bn'] = $order_bn['order_bn'].'-R1';
					}
					$order_bn['order_bn'] = $order_bn['order_bn'].'-R1';
				}
			}
			$arow[] = $order_bn['order_bn'];
			$arow[] = $row['difference_reason'];
			
			//兑礼订单的支付方式默认wechatcard
			$row['paymethod'] = 'wechatcard';
			
			
			$arow[] = $row['paymethod'];
			$arow[] = 'PG4A';
			if($row['original_type']=='refunds'){
				$arow[] =sprintf("%1\$.2f",-abs($objMath->number_plus(array($row['pay_fee'],0))));
			}else{
				$arow[] =sprintf("%1\$.2f",abs($objMath->number_plus(array($row['pay_fee'],0))));
			}
			if($row['original_type']=='payments'){
				$arow[] = $row['paymethod'].' payment '.$order_bn['order_bn'].' in '.$arow[0];
			}else{
				$arow[] = $row['paymethod'].' return '.$order_bn['order_bn'].' in '.$arow[0];
			}
			
			$ax_info[] = implode(',',$arow);
		}echo "<pre>";print_r($ax_info);
		$content = implode("\n",$ax_info);
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
		$file_brand = $ax_setting['ax_file_brand'];
		$file_prefix = $ax_setting['ax_file_prefix'];

		$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));

		$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		while(file_exists($file_name)){
			sleep(1);
			$file_arr = array($file_prefix,$file_brand,'PAYMENT',date('YmdHis',time()));
			$file_name = ROOT_DIR.'/ftp/Testing/in/'.implode('_',$file_arr).'.dat';
		}
		
		echo "<pre>";print_r($file_name);exit;
		$file = fopen($file_name,"w");
		$res = fwrite($file,$content);
		fclose($file);
		//同步AX

		if(!$res){
			return true;
		}
		$params['remote'] = basename($file_name);
		$params['local'] = $file_name;
		$params['resume'] = 0;

		$ftp_log_data = array(
				'io_type'=>'out',
				'work_type'=>'payments',
				'createtime'=>time(),
				'status'=>'prepare',
				'file_local_route'=>$params['local'],
				'file_ftp_route'=>$params['remote'],
			);
		$objLog = kernel::single('omeftp_log');
		$ftp_log_id = $objLog->write_log($ftp_log_data,'ftp');

		$ftp_flag = kernel::single('omeftp_ftp_operate')->push($params,$msg);
		if($ftp_flag){
			$objLog->update_log(array('status'=>'succ','lastmodify'=>time(),'memo'=>'上传成功！'),$ftp_log_id,'ftp');
		}else{
			$objLog->update_log(array('status'=>'fail','memo'=>$msg),$ftp_log_id,'ftp');
		}
		$paymentObj->update(array('balance_status'=>'sync'),array('statement_id'=>$payment_ids));
        $this->end(true,'同步完成');
     }
}