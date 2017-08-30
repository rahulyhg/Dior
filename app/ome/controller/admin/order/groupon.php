<?php

class ome_ctl_admin_order_groupon extends desktop_controller{

    var $name = "团购订单批量导入";
    var $workground = "order_groupon_center";
    
 	function index(){
        $this->finder('ome_mdl_order_groupon',array(
            'title'=>'团购订单批量导入',
            'actions' => array(
        		//array('label'=>'导出模板','href'=>'index.php?app=ome&ctl=admin_order_groupon&act=exportOrderTemplate','target'=>'_blank')
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>false,
             'orderBy' =>'order_groupon_id DESC'
            ));
    }
    
    function import(){
        $shopObj = &$this->app->model("shop");
        $shopData = $shopObj->getList('shop_id,name,shop_type');
        $this->pagedata['shopData'] = $shopData;
        $this->pagedata['pluginList'] =  kernel::single('ome_groupon_import')->getPluginList();
        
        $oPayment = &$this->app->model('payments');
        $aRet = $oPayment->getAccount();
        $aAccount = array('--使用已存在帐户--');
        foreach ($aRet as $v){
            $aAccount[$v['bank']."-".$v['account']] = $v['bank']." - ".$v['account'];
        }
        $this->pagedata['pay_account'] = $aAccount;
        
        $this->pagedata['typeList'] = ome_payment_type::pay_type();
        
        $payment = $oPayment->getMethods();
        $this->pagedata['payment'] = $payment;
        
        echo $this->page('admin/order/import/import.html');
    }
    
    function doImport(){
        $result = kernel::single('ome_groupon_import')->process($_POST);
        header("content-type:text/html; charset=utf-8");

         //团购订单批量导入操作日志
        $logParams = array(
            'app' => $this->app->app_id,
            'ctl' => trim($_GET['ctl']),
            'act' => trim($_GET['act']),
            'modelFullName' => '',
            'type' => 'import',
            'params' => array(),
        );
        ome_operation_log::insert('order_groupon_bat_import', $logParams);
        if($result['rsp'] == 'succ'){
            echo json_encode(array('result' => 'succ', 'msg' =>'上传成功'));
        }else{
            echo json_encode(array('result' => 'fail', 'msg' =>(array)$result['res']));
        }
    }

    function exportOrderTemplate(){
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=".date('Ymd').".csv");
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $oObj = kernel::single('ome_groupon_import');
        $title1 = $oObj->exportOrderTemplate();
        echo '"'.implode('","',$title1).'"';
    }
    
 }

?>