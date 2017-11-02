<?php
class einvoice_ctl_admin_order extends desktop_controller{

	public function __construct($app){
		parent::__construct($app);
	}

	public function index(){
		
		$actions = array(
				array('label'=>'补开发票','href'=>'index.php?app=einvoice&ctl=admin_order&act=applyEinvoice','target'=>'dialog::{width:400,height:150,title:\'重开发票\'}'),
			);
		if($_GET['view']=='2'){
			$actions[] = array('label'=>'发票冲红', 'confirm' =>'冲红将会取消发票，确认此操作？','submit'=>'index.php?app=einvoice&ctl=admin_order&act=cancelEinvoice');
		}
		$this->finder('einvoice_mdl_invoice', array(
            'title' => '发票列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
            'use_buildin_import' => false,
			//'orderBy' =>'createtime DESC',
			//'base_filter'=>array('invoice_type'=>array('active','cancel')),
			'actions'=>$actions,
        ));
	}


	public function _views(){
		
		$einvoiceMdl = app::get('einvoice')->model('invoice');
		$base_filter = array();
		
		$all_count = $einvoiceMdl->count(array('invoice_type'=>array('active','cancel')));
		$active_count = $einvoiceMdl->count(array('invoice_type'=>'active'));
		$cancel_count = $einvoiceMdl->count(array('invoice_type'=>'cancel'));
		//$active_count = $einvoiceMdl->count();
		$sub_menu = array(
            1 => array('label'=>app::get('base')->_('全部'),'filter'=>$base_filter,'addon'=>$all_count,'optional'=>false),
            2 => array('label'=>app::get('base')->_('已开票'),'filter'=>array('invoice_type'=>'active'),'addon'=>$active_count,'optional'=>false),

			3 => array('label'=>app::get('base')->_('已取消'),'filter'=>array('invoice_type'=>'cancel'),'addon'=>$cancel_count,'optional'=>false),
		//	4 => array('label'=>app::get('base')->_('开票中'),'filter'=>array('balance_status'=>'none'),'addon'=>$none_count,'optional'=>false),
        );
		return $sub_menu;
	}


	public function cancelEinvoice(){
		$this->begin('index.php?app=einvoice&ctl=admin_order&act=index&view=2');
		$id = $_POST['id'];
		$invoiceMdl = app::get('einvoice')->model('invoice');
		$info = $invoiceMdl->getList('*',array('id'=>$id));
		kernel::single('einvoice_request_invoice')->invoice_request($info[0]['order_id'],'getCancelInvoiceData');
		$this->end(true,'操作成功！');
	}

	public function applyEinvoice(){
	
		$this->display('admin/apply.html');
	}

	public function doApply(){
		$this->begin('index.php?app=einvoice&ctl=admin_order&act=index');
		$invoiceMdl = app::get('einvoice')->model('invoice');
		$orderMdl = app::get('ome')->model('orders');
		$order_bn = $_POST['order_bn'];
		$order_info = $orderMdl->getList('*',array('order_bn'=>$order_bn));

		if(empty($order_info)){
			$this->end(false,'您输入的订单号不存在！');
		}
		if($order_info[0]['is_einvoice']=='false'){
			$this->end(false,'此订单的发票类型不是电子发票，无法重开！');
		}
		$info = $invoiceMdl->getList('*',array('order_id'=>$order_info[0]['order_id'],'invoice_type'=>'active'));


		if($info){
			$this->end(false,'此订单的发票尚未冲红，无法重开！');
		}

		kernel::single('einvoice_request_invoice')->invoice_request($order_info[0]['order_id'],'getApplyInvoiceData');


		$this->end(true,'重开发票操作完成！');

		
	}

	

}
