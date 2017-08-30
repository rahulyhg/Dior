<?php
class ome_ctl_admin_balance_pay extends desktop_controller{
	var $name = "对账管理";
    var $workground = "finance_center";

	public function index(){

		$actions = array(
                    array('label'=>'导入账单','href'=>'index.php?app=ome&ctl=admin_balance_pay&act=importBill','target'=>'dialog::{width:400,height:150,title:\'导入\'}'),
					array('label'=>'同步AX','submit'=>'index.php?app=ome&ctl=admin_balance_pay&act=sync_ax'),
                );
		
		$params = array(
				'title'=>'收款对账',
				'actions'=>$actions,
				'use_buildin_new_dialog' => false,
				'use_buildin_set_tag'=>false,
				'use_buildin_recycle'=>false,
				'use_buildin_export'=>false,
				'use_buildin_import'=>false,
				'base_filter' =>$base_filter,
			);
		$this->finder('ome_mdl_balance_account',$params);
	}

	 function _views(){
		 $objPayment = app::get('ome')->model('payments');
		 $all_count = $objPayment->count();
		 $none_count = $objPayment->count(array('balance_status'=>'none'));
		 $conf_count = $objPayment->count(array('balance_status'=>array('auto','hand')));
		 $requ_count = $objPayment->count(array('balance_status'=>'require'));
		 $sync_count = $objPayment->count(array('balance_status'=>'sync'));
		 $sub_menu = array(
            1 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'addon'=>$all_count,'optional'=>false),
            2 => array('label'=>app::get('base')->_('未对账'),'filter'=>array('balance_status'=>'none'),'addon'=>$none_count,'optional'=>false),
            3 => array('label'=>app::get('base')->_('已对账'),'filter'=>array('balance_status'=>array('auto','hand')),'addon'=>$conf_count,'optional'=>false),
            4 => array('label'=>app::get('base')->_('人工确认'),'filter'=>array('balance_status'=>'require'),'addon'=>$requ_count,'optional'=>false),
			5 => array('label'=>app::get('base')->_('已同步'),'filter'=>array('balance_status'=>'sync'),'addon'=>$sync_count,'optional'=>false),
			
        );
//print_r($sub_menu);exit;
		return $sub_menu;
	 }

	 public function importBill(){
		$this->pagedata['finder_id'] = $_GET['finder_id'];
		$this->display('admin/balance/import.html');
	 }


	 public function do_import(){
		 $this->begin('index.php?app=ome&ctl=admin_balance_pay&act=index');
		 @set_time_limit(600);   @ini_set('memory_limit','1024M');
        $fileName = $_FILES['import_file']['name'];
        if( !$fileName ){
			$this->end(false,'上传失败，未上传文件');
        }

		$pathinfo = pathinfo($fileName);

		if($pathinfo['extension']!='csv'){
			$this->end(false,'上传失败，导入格式错误');
		}

		$oImportType = kernel::single('omecsv_io_type_csv');

		try {
            # 条数限制
            $sheetInfo = $oImportType->listWorksheetInfo($_FILES['import_file']['tmp_name']);
            if ((int)$sheetInfo['totalRows'] > $oImportType->limitRow ) {
				$this->end(false,'上传失败，导入数据量过大，请减至'.$oImportType->limitRow.'单以下');
            }

            $contents = array();
            $oImportType->fgethandle($_FILES['import_file']['tmp_name'],$contents);
            $model->import_totalRows = count($contents);
        } catch (Exception $e) {
            $msg = $e->getMessage();
			$this->end(false,$msg);
        }
		$pay_type = explode('_',$pathinfo['filename']);
		$balance_import_account = kernel::single('ome_balance_to_import');
		$balance_import_account->pay_type = $pay_type[0];
		$re = $balance_import_account->do_paymens_bill($contents,$msg);
		//error_log(var_export($pathinfo,true),3,'f:/dd.txt');
		//error_log(var_export($contents,true),3,'f:/dd.txt');
//		echo "<pre>";print_r($_GET);exit;
		if($re){
			header("content-type:text/html; charset=utf-8");
			echo "<script>parent.MessageBox.success(\"上传成功\");alert(\"上传成功\");if(parent.$('import_form').getParent('.dialog'))parent.$('import_form').getParent('.dialog').retrieve('instance').close();if(parent.window.finderGroup&&parent.window.finderGroup['".$_GET['finder_id']."'])parent.window.finderGroup['".$_GET['finder_id']."'].refresh();</script>";
			$this->end(true,'操作成功');
		}else{
			$this->end(false,$msg);
		}
		
	 }

	 public function sync_ax(){
		$this->begin('index.php?app=ome&ctl=admin_balance_pay&act=index');
		$payment_ids = $_POST['payment_id'];
		$paymentObj = app::get('ome')->model('payments');
		$payments = $paymentObj->getList('*',array('payment_id'=>$payment_ids));
		foreach($payments as $payment){
			if(!in_array($payment['balance_status'],array('auto','hand'))){
				$this->end(false,'存在未完成对账的文件');
			}
		}
		$paymentObj->update(array('balance_status'=>'sync'),array('payment_id'=>$payment_ids));
		$this->end(true,'同步完成');
		
	 }

	 public function confirm_status($payment_id){
		 $orderObj = app::get('ome')->model('orders');
		 $paymentObj = app::get('ome')->model('payments');
		 $payments = $paymentObj->dump($payment_id);
		 $filter = array('order_id'=>$payments['order_id']);
		 $order = $orderObj->dump($filter,'order_bn');
		 $payments['order_bn'] = $order['order_bn'];
		 $this->pagedata['payments'] = $payments;
		 $this->page('admin/balance/comfirm_status.html');
	 }

	 public function do_confirm_status(){
		  $this->begin();
		  $paymentObj = app::get('ome')->model('payments');
		  $pData['payment_id'] = $_POST['payment_id'];
		  $pData['difference_reason'] = $_POST['difference_reason'];
		  $pData['balance_status'] = 'hand';

		  $paymentObj->save($pData);
		  $this->end(true,'确认成功');
	 }

}