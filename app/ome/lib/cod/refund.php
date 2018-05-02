<?php
class ome_cod_refund{
	
	function __construct($app){
        $this->app = $app;
    }
	
	function autoRefund($arrRefund){
		$objOrder = &app::get('ome')->model('orders');
		$oRefund_apply = &$this->app->model('refund_apply');
		$oShop = &app::get('ome')->model ( 'shop' );
		$mdl_reship = app::get('ome')->model('reship');
		$arrRefund=$arrRefund[0];
		$data=array();
		$refund_money=$arrRefund['money'];
		$order_id=$arrRefund['order_id'];
		$bcmoney = 0;
        $countPrice=$refund_money;
        $totalPrice=0;
        $totalPrice=$countPrice+$bcmoney;
		
		//如果是MCD换货订单申请退款 关联原始支付单号
		$relate_order_id=NULL;
	    if($arrRefund['createway']=="after"){
			$relate_order_id=$mdl_reship->getOriginalOrderId($arrRefund['relate_order_bn']);
		}else{
		    $relate_order_id=$order_id;
		}
		
        $z_r_apply_bn=$objOrder->db->selectrow("SELECT payment_bn FROM sdb_ome_payments WHERE order_id='$relate_order_id' AND status='succ'");
	    $refund_apply_bn=$z_r_apply_bn['payment_bn'];
	    $refund_apply_bn=$oRefund_apply->checkRefundApplyBn($refund_apply_bn);
        $source='local';
       
		$data=array(
				 'order_id'=>$arrRefund['order_id'],
				 'reship_id'=>$arrRefund['reship_id'],
				 'shop_id'=>$arrRefund['shop_id'],
				 'order_bn'=>$arrRefund['order_bn'],
				 'pay_type'=>'online',
				 'payment'=>'4',
				 'account'=>'',
                 'return_id'=>$data['return_id'],
                 'refund_apply_bn'=>$refund_apply_bn,
                 'money'=>$totalPrice,
                 'bcmoney'=>$bcmoney,
                 'apply_op_id'=>kernel::single('desktop_user')->get_id(),
                 'memo'=>'cod自动生成的退款单',
                 'verify_op_id' =>kernel::single('desktop_user')->get_id(),
                 'addon' => serialize(array('return_id'=>$data['return_id'])),
                 'refund_refer' => 1,
        );
        $shop_type = $oShop->getShoptype($arrRefund['shop_id']);
        $data['shop_type'] = $shop_type;
       	$data['create_time'] = time();
		//echo "<pre>";print_r($data);exit();
		if($oRefund_apply->save($data)){
			$z_refund_id=$data['apply_id'];
			if($arrRefund['createway']=="after"){
				$arrOriginalOrder=array();
				$arrOriginalOrder=$mdl_reship->getOriginalOrder($arrRefund['order_bn']);
				$order_bn=$arrOriginalOrder['relate_order_bn'];//老订单号
			}else{
				$order_bn=$arrRefund['order_bn'];
			}
			$z_money=$totalPrice;
			$z_refund_info[] = array('oms_rma_id'=>$arrRefund['reship_id']);
			
			kernel::single('omemagento_service_order')->update_status($order_bn,'refund_required','',time(),$z_refund_info);
			app::get('ome')->model('refund_apply')->sendRefundToM($z_refund_id,$order_bn,$z_money,$arrRefund['reship_id']);
			return true;
		}
		return false;
		
	}
	
	function CallBackRefund($data){
		$objRefund=$this->app->model('refund_apply');
		$objOrder = kernel::single("ome_mdl_orders");
		//预处理
		$arrData=array();
		$count=count($data);
		if($count <= 0){  
       	    return false;  
   		}  
		for($i=0; $i<$count; $i++){
			for($k=$count-1; $k>$i; $k--){
				if(strtotime($data[$k][20]) > strtotime($data[$k-1][20])){
					$tmp = $data[$k];
					$data[$k] = $data[$k-1];
					$data[$k-1] = $tmp;
				}
			}
		}
		$arrLin=array();
		foreach($data as $k=>$v){
			if($v[0]==""||empty($v[0])){//删空
				unset($data[$k]);
				continue;
			}
			$brand=substr($v['17'],0,3);
			$apply_id=substr($v['17'],3);
			
			if((isset($arrLin[$v['17']])&&$arrLin[$v['17']]=="1")||$brand!="pcd"){//删重复 区分dior  娇兰
				unset($data[$k]);
				continue;
			}
			$arrRefund=$objRefund->getList('apply_id',array('apply_id'=>$apply_id));//
			if(empty($arrRefund[0]['apply_id'])){
				unset($data[$k]);
				continue;
			}
			
			$data[$k][17]=$apply_id;
			$data[$k]['refundTime']=strtotime($v[20]);
			$arrLin[$v['17']]=1;
		}
		if(count($data) <= 0){  
       	    return false;  
   		}  
		
			foreach($data as $key=>$value){
				$apply_id=$value['17'];//
				$status=$value['18'];
				$refundTime=$value['refundTime'];
				if(!empty($apply_id)){//updateCodRefund
					
					if($status=="交易成功"||$status=="银行已汇出"){
						$objRefund->updateCodRefund($apply_id,$refundTime);
					}else{//交易失败
						$arrOrderBn=$objOrder->db->select("SELECT o.order_bn,a.reship_id,o.order_id,o.shop_id,o.createway,o.relate_order_bn,a.money FROM sdb_ome_refund_apply a LEFT JOIN sdb_ome_orders o ON a.order_id=o.order_id where a.apply_id='$apply_id'");
						$objOrder->db->exec("UPDATE sdb_ome_refund_apply SET status='6' WHERE apply_id='$apply_id'");
					    
						$this->app->model('refund_apply')->sendMagentoAndEinvoiceData($arrOrderBn['0']['order_id'],$apply_id,2);
						
						if($this->autoRefund($arrOrderBn)){
							error_log('excel自动生成订单成功:'.$arrOrderBn[0]['order_bn'],3,DATA_DIR.'/mrefund/'.date("Ymd").'zjrorder.txt');
						}else{
							error_log('excel自动生成订单失败:'.$arrOrderBn[0]['order_bn'],3,DATA_DIR.'/mrefund/'.date("Ymd").'zjrorder.txt');
					    }
						
					}
				}
			}
		 
	}
	
 
}
?>
 