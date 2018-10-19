<?php

class ome_mdl_refund_apply extends dbeav_model{
    //是否有导出配置
    var $has_export_cnf = true;
    //所用户信息
    static $__USERS = null;

    var $pay_type = array (
        'online' => '在线支付',
        'offline' => '线下支付',
        'deposit' => '预存款支付',
      );

    var $defaultOrder = array('create_time DESC');

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        if(isset($filter['order_bn'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id', array('order_bn|has'=>$filter['order_bn']), 0, -1);
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $archiveObj = app::get('archive')->model('orders');
            $archives = $archiveObj->getList('order_id', array('order_bn|has'=>$filter['order_bn']), 0, -1);
            foreach ($archives as $archive ) {
                $orderId[] = $archive['order_id'];
            }
            if (empty($orderId)){
                $orderId[] = '0';
            }
            $where .= ' AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['order_bn']);
        }
        if (isset($filter['member_uname'])){
            $memberObj = &$this->app->model("members");
            $rows = $memberObj->getList('member_id',array('uname|has'=>$filter['member_uname']));
            $memberId[] = 0;
            foreach($rows as $row){
                $memberId[] = $row['member_id'];
            }

            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id', array('member_id'=>$memberId));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $where .= ' AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['member_uname']);
        }
        if (isset($filter['ship_name'])){
            $orderObj = &$this->app->model("orders");
            $rows = $orderObj->getList('order_id', array('ship_name|has'=>$filter['ship_name']));
            $orderId[] = 0;
            foreach($rows as $row){
                $orderId[] = $row['order_id'];
            }
            $where .= ' AND order_id IN ('.implode(',', $orderId).')';
            unset($filter['ship_name']);
        }
         #退款原因,模糊搜索
         if(isset($filter['memo'])){
            $_filter['filter_sql'] = ' memo like \''.$filter['memo'].'%\'';
            $have_memo = $this->app->model('refund_apply')->getList('apply_id',$_filter,0,-1);
            if(!empty($have_memo)){
                foreach($have_memo as $v){
                    $_apply_id[$v['apply_id']] = $v['apply_id'];
                }
                $where .= ' AND apply_id IN ('.implode(',', $_apply_id).')';
                unset($filter['memo']);
            }
        }
		 
        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }

    function refund_apply_detail($refapply_id){
    	$refapply_detail = $this->dump($refapply_id);
        $product_data = $refapply_detail['product_data'];
        if ($product_data) {
            $items = unserialize($product_data);
        }
        $refapply_detail['items'] = $items;
        if ($refapply_detail['payment']){
    	    $sql = "SELECT custom_name FROM sdb_ome_payment_cfg WHERE id=".$refapply_detail['payment'];
    	    $payment_cfg = $this->db->selectrow($sql);
            $refapply_detail['payment_name'] = $payment_cfg['custom_name'];
        }else {
            $refapply_detail['payment_name'] = '';
        }

    	$refapply_detail['type'] = $this->pay_type[$refapply_detail['pay_type']];
    	return $refapply_detail;
    }

    /* create_refund_apply 添加申请退款单
     * @param sdf $sdf
     * @return sdf
     */
    function create_refund_apply(&$sdf){
        $this->save($sdf);
    }

    function save(&$refund_data,$mustUpdate=NULL){
    	return parent::save($refund_data,$mustUpdate,true);
    }

    /**
     * 快捷搜索
     */
    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'order_bn' => '订单号',
            'member_uname'=>app::get('base')->_('用户名'),
            'ship_name'=>app::get('base')->_('收货人'),
        );
        return array_merge($parentOptions,$childOptions);
    }

    /**
     * 检查是否要将订单设为取消
     * 只有 全额退款并且为未发货的订单才会取消
     */
    function check_iscancel($order_id,$memo=null){
        $oShop = &$this->app->model('shop');
        $oOrder = app::get('ome')->model('orders');
        $order_detaillist = $oOrder->dump($order_id);
        $shop_detail = $oShop->dump(array('shop_id'=>$order_detaillist['shop_id']),'node_id');

          //只有未发货的才会取消订单
        if($order_detaillist['ship_status'] == 0){
          //增加订单取消的流程
          $memo = $memo?$memo:'订单全额退款后取消！';

          $mod = 'sync';
          $c2c_shop_list = ome_shop_type::shop_list();
          if(in_array($order_detaillist['shop_type'],$c2c_shop_list) || $order_detaillist['source'] == 'local' || !$shop_detail['node_id']){
            $mod = 'async';
          }
          $oOrder->cancel($order_id,$memo,true,$mod);
       }
    }

    /*
     * 退款申请单号
     *
     * @return 退款单号
     */
     function gen_id(){
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $refund_apply_bn = date("YmdH").'14'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $this->db->selectrow('select refund_apply_bn from sdb_ome_refund_apply where refund_apply_bn =\''.$refund_apply_bn.'\'');
        }while($row);
        return $refund_apply_bn;
    }

    /**
     * 单据来源.
     * @param   
     * @return  string
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_source($row)
    {
        if ($row == 'local') {
            $source = '本地';
        }else if($row == 'matrix'){
           $source = '线上';
        }else if ($row == 'archive') {
            $source = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', '归档', '归档', '归档');
        }else {
            $source = '-';
        }
        return $source;
    }

    /**
     * 退款原因
     * @param   
     * @return  string
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_memo($row)
    {
        if ($row) {
            $reason = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'green', $row, $row, $row);
            return $reason;
        }
        
    }

    public function modifier_apply_op_id($row){
        switch ($row) {
            
            case 16777215:
                $ret = '系统';
                break;
            default:
                $ret = $this->_getUserName($row);
                break;
        }

        return $ret;
    }

    /**
     * 获取用户名
     *
     * @param Integer $gid
     * @return String;
     */
    private function _getUserName($uid) {
        if (self::$__USERS === null) {

            self::$__USERS = array();
            $rows = app::get('desktop')->model('users')->getList('*');
            foreach((array) $rows as $row) {
                self::$__USERS[$row['user_id']] = $row['name'];
            }
        }

        if (isset(self::$__USERS[$uid])) {

            return self::$__USERS[$uid];
        } else {

            return '系统';
        }
    }
	
	function checkRefundApplyBn($trade_no){
		$oRefaccept = &$this->app->model('refund_apply');
		$arrTrade_no=$oRefaccept->db->select("SELECT apply_id,refund_apply_bn FROM `sdb_ome_refund_apply` where refund_apply_bn like '$trade_no%' ORDER BY apply_id ASC");
		if(!empty($arrTrade_no['0']['refund_apply_bn'])){
			foreach($arrTrade_no as $refund_apply_bn){
				$refund_apply_bn=$refund_apply_bn['refund_apply_bn'];
			}
			if(strpos($refund_apply_bn,'_')!==false){
				$id=substr($refund_apply_bn,strpos($refund_apply_bn,'_')+1);
				$id++;
				$refund_apply_bn=$trade_no."_".$id;
			}else{
				$refund_apply_bn=$refund_apply_bn."_1";
			}
			return $refund_apply_bn;
		}else{
			return $trade_no;
		}
	}
	
	function updateCodRefund($apply_id,$refundTime){
	$oRefaccept = &$this->app->model('refund_apply');
        $oOrder = &$this->app->model('orders');
	    $deoObj = &app::get('ome')->model('delivery_order');
		$oRefund = &$this->app->model('refunds');
        $oLoger = &$this->app->model('operation_log');
        $objShop = &$this->app->model('shop');
		
	$trade_no['apply_id']=$apply_id;
		$apply_detail = $oRefaccept->refund_apply_detail($trade_no['apply_id']);
					$apply_id=$trade_no['apply_id'];
					if (in_array($apply_detail['status'],array('2','5','6'))){
						$db = kernel::database();
						$transaction_status = $db->beginTransaction();
					
						$order_id = $apply_detail['order_id'];
						$order_detail = $oOrder->order_detail($order_id);
						$ids = $deoObj->getList('delivery_id',array('order_id'=>$order_id));
						//售后申请单处理
						$oretrun_refund_apply = &$this->app->model('return_refund_apply');
						$return_refund_appinfo = $oretrun_refund_apply->dump(array('refund_apply_id'=>$trade_no['apply_id']));
						if ($return_refund_appinfo['return_id'])
						{
							$oreturn = &$this->app->model('return_product');
							$return_info = $oreturn->product_detail($return_refund_appinfo['return_id']);
							if (($return_info['refundmoney']+$apply_detail['money'])>$return_info['tmoney'])
							{
								//$this->end(false, '申请退款金额大于售后的退款金额！');
							}
							$return_info['refundmoney'] = $return_info['refundmoney']+$apply_detail['money'];
	
							if(!$oreturn->save($return_info)){
								 $db->rollback();
								 return false;
							}
	
							$oLoger->write_log('return@ome',$return_info['return_id'],"售后退款成功。");
						}
						 //订单信息更新
						$orderdata = array();
						if (round($apply_detail['money'],3)== round(($order_detail['payed']),3))
						{
							$orderdata['pay_status'] = 5;
						}
						else
						{
							$orderdata['pay_status'] = 4;

						}
						$orderdata['order_id'] =  $apply_detail['order_id'];
						$orderdata['payed'] = $order_detail['payed'] - ($apply_detail['money']-$apply_detail['bcmoney']);//需要将补偿运费减掉
						
						if(!$oOrder->save($orderdata)){
							$db->rollback();
							return false;
						}

                   		$oLoger->write_log('order_modify@ome',$orderdata['order_id'],$fail_msg."退款成功，更新订单退款金额。");
						
						//退款申请状态更新
						$applydata = array();
						$applydata['apply_id'] = $apply_id;
						$applydata['status'] = 4;//已经退款
						$applydata['refunded'] = $apply_detail['money'];// + $order_detail['payinfo']['cost_payment'];
						$applydata['last_modified'] = time();
						$oRefaccept->save($applydata,true);
						$oLoger->write_log('refund_apply@ome',$applydata['apply_id'],"退款成功，更新退款申请状态。");
						//更新售后退款金额
						$return_id = intval($_POST['return_id']);
						if(!empty($return_id)){
						   $sql = "UPDATE `sdb_ome_return_product` SET `refundmoney`=IFNULL(`refundmoney`,0)+{$apply_detail['money']} WHERE `return_id`='".$return_id."'";
						   kernel::database()->exec($sql);
						}
						 //单据生成：生成退款单
						$refunddata = array();
						$refund_apply_bn = $apply_detail['refund_apply_bn'];
						if ($refund_apply_bn){
							$refund_bn = $refund_apply_bn;
						}else{
							$refund_bn = $oRefund->gen_id();
						}
						$refunddata['refund_bn'] = $refund_bn;
						$refunddata['order_id'] = $apply_detail['order_id'];
						$refunddata['shop_id'] = $order_detail['shop_id'];
						$refunddata['pay_account'] = $apply_detail['pay_account'];
						$refunddata['currency'] = $order_detail['currency'];
						$refunddata['money'] = $apply_detail['money'];
						$refunddata['paycost'] = 0;//没有第三方费用
						$refunddata['cur_money'] = $apply_detail['money'];//汇率计算 TODO:应该为汇率后的金额，暂时是人民币金额
						$refunddata['pay_type'] = $apply_detail['pay_type'];
						$refunddata['payment'] = $apply_detail['payment'];
						$paymethods = ome_payment_type::pay_type();
						$refunddata['paymethod'] = $paymethods[$refunddata['pay_type']];
						//Todo ：确认paymethod
						$opInfo = kernel::single('ome_func')->getDesktopUser();
						$refunddata['op_id'] = $opInfo['op_id'];
	
						$refunddata['t_ready'] = time();
						$refunddata['t_sent'] = time();
						$refunddata['status'] = "succ";#支付状态
						$refunddata['statement_status'] ='true';
						$refunddata['memo'] = $apply_detail['memo'];
						if(!$oRefund->save($refunddata)){
							$db->rollback();
							return false;
						}
						
						$objStatement = &$this->app->model('statement');
						$arrStatement=array();
						$arrStatement['original_bn'] = $refund_bn;
						$arrStatement['order_id'] = $apply_detail['order_id'];
						$arrStatement['money'] = "-".$apply_detail['money'];
						$arrStatement['paycost'] =0;
						$arrStatement['cur_money'] = "-".$apply_detail['money'];
						$arrStatement['payment'] = 4;
						$arrStatement['paymethod'] = 'cod';
						$arrStatement['memo'] = '';
					
						$sql="SELECT trade_no,t_begin FROM sdb_ome_payments WHERE order_id='".$apply_detail['order_id']."'";
						$arrS_trade_no=$this->db->select($sql);
					
						$arrStatement['trade_no'] = $arrS_trade_no[0]['trade_no'];
						$arrStatement['original_type'] = 'refunds';
						$arrStatement['tatal_amount'] = "-".$apply_detail['money'];
						$arrStatement['balance_status'] = 'auto';
						$arrStatement['importer_time'] = time();
						$arrStatement['cod_time'] ='second';
						$arrStatement['pay_time'] =$refundTime;
						if(!$objStatement->save($arrStatement)){
							$db->rollback();
							return false;
						}

								
						$oLoger->write_log('refund_accept@ome',$refunddata['refund_id'],"退款成功，生成退款单".$refunddata['refund_bn']);
						if(!empty($return_id)){
						    $return_data = array ('return_id' => $_POST['return_id'], 'status' => '4', 'refundmoney'=>$refunddata['money'], 'last_modified' => time () );
						    $Oreturn_product = $this->app->model('return_product');
						    if(!$Oreturn_product->update_status ( $return_data )){
								$db->rollback();
								return false;
							}
						}
						
						$db->commit($transaction_status);
						
						kernel::single('ome_order_func')->update_order_pay_status($apply_detail['order_id']);
						//传给买尽头
						$this->sendMagentoAndEinvoiceData($apply_detail['order_id'],$apply_id);
						return true;
					}//if	
	}
	
	function updateAlipayRefundFail($batch_no,$trade_no,$money){
		$arrRefund=$this->db->select("SELECT apply_id,order_id FROM sdb_ome_refund_apply WHERE refund_apply_bn LIKE '$trade_no%' AND alipaybatchno='$batch_no' AND status='5'");
		
		if(!empty($arrRefund['0']['apply_id'])){
			foreach($arrRefund as $trade_no){
				$apply_id=$trade_no['apply_id'];
				$this->db->exec("UPDATE sdb_ome_refund_apply SET status='2',apimsg='退款失败' WHERE apply_id='$apply_id'");
				
				$this->sendMagentoAndEinvoiceData($trade_no['order_id'],$apply_id,2);
			}
		}
	}
	
	function updateAlipayRefund($batch_no,$trade_no,$money){
		$oRefaccept = &$this->app->model('refund_apply');
        $oOrder = &$this->app->model('orders');
	    $deoObj = &app::get('ome')->model('delivery_order');
		$oRefund = &$this->app->model('refunds');
        $oLoger = &$this->app->model('operation_log');
        $objShop = &$this->app->model('shop');
		$arrRefund=$this->db->select("SELECT apply_id FROM sdb_ome_refund_apply WHERE refund_apply_bn LIKE '$trade_no%' AND alipaybatchno='$batch_no' AND status='5'");
		if(!empty($arrRefund['0']['apply_id'])){
			foreach($arrRefund as $trade_no){
				    $apply_detail = $oRefaccept->refund_apply_detail($trade_no['apply_id']);
					$apply_id=$trade_no['apply_id'];
					if (in_array($apply_detail['status'],array('2','5','6'))){
						$db = kernel::database();
						$transaction_status = $db->beginTransaction();
					
						$order_id = $apply_detail['order_id'];
						$order_detail = $oOrder->order_detail($order_id);
						$ids = $deoObj->getList('delivery_id',array('order_id'=>$order_id));
						//售后申请单处理
						$oretrun_refund_apply = &$this->app->model('return_refund_apply');
						$return_refund_appinfo = $oretrun_refund_apply->dump(array('refund_apply_id'=>$trade_no['apply_id']));
						if ($return_refund_appinfo['return_id'])
						{
							$oreturn = &$this->app->model('return_product');
							$return_info = $oreturn->product_detail($return_refund_appinfo['return_id']);
							if (($return_info['refundmoney']+$apply_detail['money'])>$return_info['tmoney'])
							{
								//$this->end(false, '申请退款金额大于售后的退款金额！');
							}
							$return_info['refundmoney'] = $return_info['refundmoney']+$apply_detail['money'];
	
							if(!$oreturn->save($return_info)){
								 $db->rollback();
								 return false;
							}
	
							$oLoger->write_log('return@ome',$return_info['return_id'],"售后退款成功。");
						}
						 //订单信息更新
						$orderdata = array();
						if (round($apply_detail['money'],3)== round(($order_detail['payed']),3))
						{
							$orderdata['pay_status'] = 5;
						}
						else
						{
							$orderdata['pay_status'] = 4;

						}
						$orderdata['order_id'] =  $apply_detail['order_id'];
						$orderdata['payed'] = $order_detail['payed'] - ($apply_detail['money']-$apply_detail['bcmoney']);//需要将补偿运费减掉
						
						if(!$oOrder->save($orderdata)){
							$db->rollback();
							return false;
						}

                   		$oLoger->write_log('order_modify@ome',$orderdata['order_id'],$fail_msg."退款成功，更新订单退款金额。");
						
						//退款申请状态更新
						$applydata = array();
						$applydata['apply_id'] = $apply_id;
						$applydata['status'] = 4;//已经退款
						$applydata['refunded'] = $apply_detail['money'];// + $order_detail['payinfo']['cost_payment'];
						$applydata['last_modified'] = time();
						$oRefaccept->save($applydata,true);
						$oLoger->write_log('refund_apply@ome',$applydata['apply_id'],"退款成功，更新退款申请状态。");
						//更新售后退款金额
						$return_id = intval($_POST['return_id']);
						if(!empty($return_id)){
						   $sql = "UPDATE `sdb_ome_return_product` SET `refundmoney`=IFNULL(`refundmoney`,0)+{$apply_detail['money']} WHERE `return_id`='".$return_id."'";
						   kernel::database()->exec($sql);
						}
						 //单据生成：生成退款单
						$refunddata = array();
						$refund_apply_bn = $apply_detail['refund_apply_bn'];
						if ($refund_apply_bn){
							$refund_bn = $refund_apply_bn;
						}else{
							$refund_bn = $oRefund->gen_id();
						}
						$refunddata['refund_bn'] = $refund_bn;
						$refunddata['order_id'] = $apply_detail['order_id'];
						$refunddata['shop_id'] = $order_detail['shop_id'];
						$refunddata['pay_account'] = $apply_detail['pay_account'];
						$refunddata['currency'] = $order_detail['currency'];
						$refunddata['money'] = $apply_detail['money'];
						$refunddata['paycost'] = 0;//没有第三方费用
						$refunddata['cur_money'] = $apply_detail['money'];//汇率计算 TODO:应该为汇率后的金额，暂时是人民币金额
						$refunddata['pay_type'] = $apply_detail['pay_type'];
						$refunddata['payment'] = $apply_detail['payment'];
						$paymethods = ome_payment_type::pay_type();
						$refunddata['paymethod'] = $paymethods[$refunddata['pay_type']];
						//Todo ：确认paymethod
						$opInfo = kernel::single('ome_func')->getDesktopUser();
						$refunddata['op_id'] = $opInfo['op_id'];
	
						$refunddata['t_ready'] = time();
						$refunddata['t_sent'] = time();
						$refunddata['status'] = "succ";#支付状态
						$refunddata['memo'] = $apply_detail['memo'];
						if(!$oRefund->save($refunddata)){
							$db->rollback();
							return false;
						}
						
						
						$oLoger->write_log('refund_accept@ome',$refunddata['refund_id'],"退款成功，生成退款单".$refunddata['refund_bn']);
						if(!empty($return_id)){
						    $return_data = array ('return_id' => $_POST['return_id'], 'status' => '4', 'refundmoney'=>$refunddata['money'], 'last_modified' => time () );
						    $Oreturn_product = $this->app->model('return_product');
						    if(!$Oreturn_product->update_status ( $return_data )){
								$db->rollback();
								return false;
							}
						}
						
						$db->commit($transaction_status);
						
						kernel::single('ome_order_func')->update_order_pay_status($apply_detail['order_id']);
						
						$this->sendMagentoAndEinvoiceData($apply_detail['order_id'],$apply_id);
					}//if			
			}//foreach
		}//if
		
	}
	
	function updateWxRefund(){
		$oRefaccept = &$this->app->model('refund_apply');
        $oOrder = &$this->app->model('orders');
	    $deoObj = &app::get('ome')->model('delivery_order');
		$oRefund = &$this->app->model('refunds');
        $oLoger = &$this->app->model('operation_log');
        $objShop = &$this->app->model('shop');

		$arrRefund=$this->db->select("SELECT o.trade_no,o.order_id,r.apply_id,r.wxpaybatchno FROM sdb_ome_refund_apply r LEFT JOIN sdb_ome_payments o ON r.order_id=o.order_id WHERE r.wxstatus='true' AND (r.status='5' OR r.status='6')");
		
		if(!empty($arrRefund[0]['apply_id'])){
		    foreach($arrRefund as $trade_no){
				$processing=false;
			    if(kernel::single('ome_wxpay_refund')->checkRefund($trade_no,$processing)){
					if($processing){
						continue;//退款中啥都不做
					}
				    $apply_detail = $oRefaccept->refund_apply_detail($trade_no['apply_id']);
					$apply_id=$trade_no['apply_id'];
					if (in_array($apply_detail['status'],array('2','5','6'))){
						$db=NULL;
						$db = kernel::database();
						$transaction_status = $db->beginTransaction();
					
						$order_id = $apply_detail['order_id'];
						$order_detail = $oOrder->order_detail($order_id);
						$ids = $deoObj->getList('delivery_id',array('order_id'=>$order_id));
						//售后申请单处理
						$oretrun_refund_apply = &$this->app->model('return_refund_apply');
						$return_refund_appinfo = $oretrun_refund_apply->dump(array('refund_apply_id'=>$trade_no['apply_id']));
						if ($return_refund_appinfo['return_id'])
						{
							$oreturn = &$this->app->model('return_product');
							$return_info = $oreturn->product_detail($return_refund_appinfo['return_id']);
							if (($return_info['refundmoney']+$apply_detail['money'])>$return_info['tmoney'])
							{
								//$this->end(false, '申请退款金额大于售后的退款金额！');
							}
							$return_info['refundmoney'] = $return_info['refundmoney']+$apply_detail['money'];
	
							if(!$oreturn->save($return_info)){
								 $db->rollback();
								 continue;
							}
	
							$oLoger->write_log('return@ome',$return_info['return_id'],"售后退款成功。");
						}
						 //订单信息更新
						$orderdata = array();
						if (round($apply_detail['money'],3)== round(($order_detail['payed']),3))
						{
							$orderdata['pay_status'] = 5;
						}
						else
						{
							$orderdata['pay_status'] = 4;

						}
						$orderdata['order_id'] =  $apply_detail['order_id'];
						$orderdata['payed'] = $order_detail['payed'] - ($apply_detail['money']-$apply_detail['bcmoney']);//需要将补偿运费减掉
						
						if(!$oOrder->save($orderdata)){
							$db->rollback();
							continue;
						}

                   		$oLoger->write_log('order_modify@ome',$orderdata['order_id'],$fail_msg."退款成功，更新订单退款金额。");
						
						//退款申请状态更新
						$applydata = array();
						$applydata['apply_id'] = $apply_id;
						$applydata['status'] = 4;//已经退款
						$applydata['refunded'] = $apply_detail['money'];// + $order_detail['payinfo']['cost_payment'];
						$applydata['last_modified'] = time();
						$oRefaccept->save($applydata,true);
						$oLoger->write_log('refund_apply@ome',$applydata['apply_id'],"退款成功，更新退款申请状态。");
						//更新售后退款金额
						$return_id = intval($_POST['return_id']);
						if(!empty($return_id)){
						   $sql = "UPDATE `sdb_ome_return_product` SET `refundmoney`=IFNULL(`refundmoney`,0)+{$apply_detail['money']} WHERE `return_id`='".$return_id."'";
						   kernel::database()->exec($sql);
						}
						 //单据生成：生成退款单
						$refunddata = array();
						$refund_apply_bn = $apply_detail['refund_apply_bn'];
						if ($refund_apply_bn){
							$refund_bn = $refund_apply_bn;
						}else{
							$refund_bn = $oRefund->gen_id();
						}
						$refunddata['refund_bn'] = $refund_bn;
						$refunddata['order_id'] = $apply_detail['order_id'];
						$refunddata['shop_id'] = $order_detail['shop_id'];
						$refunddata['pay_account'] = $apply_detail['pay_account'];
						$refunddata['currency'] = $order_detail['currency'];
						$refunddata['money'] = $apply_detail['money'];
						$refunddata['paycost'] = 0;//没有第三方费用
						$refunddata['cur_money'] = $apply_detail['money'];//汇率计算 TODO:应该为汇率后的金额，暂时是人民币金额
						$refunddata['pay_type'] = $apply_detail['pay_type'];
						$refunddata['payment'] = $apply_detail['payment'];
						$paymethods = ome_payment_type::pay_type();
						$refunddata['paymethod'] = $paymethods[$refunddata['pay_type']];
						//Todo ：确认paymethod
						$opInfo = kernel::single('ome_func')->getDesktopUser();
						$refunddata['op_id'] = $opInfo['op_id'];
	
						$refunddata['t_ready'] = time();
						$refunddata['t_sent'] = time();
						$refunddata['status'] = "succ";#支付状态
						$refunddata['memo'] = $apply_detail['memo'];
						$refunddata['trade_no'] = $apply_detail['wxpaybatchno'];
						if(!$oRefund->save($refunddata)){
							$db->rollback();
							continue;
						}
						
						$oLoger->write_log('refund_accept@ome',$refunddata['refund_id'],"退款成功，生成退款单".$refunddata['refund_bn']);
						if(!empty($return_id)){
						    $return_data = array ('return_id' => $_POST['return_id'], 'status' => '4', 'refundmoney'=>$refunddata['money'], 'last_modified' => time () );
						    $Oreturn_product = $this->app->model('return_product');
						    if(!$Oreturn_product->update_status ( $return_data )){
								$db->rollback();
								continue;
							}
						}
						
						$db->commit($transaction_status);
						
						kernel::single('ome_order_func')->update_order_pay_status($apply_detail['order_id']);
						
						$this->sendMagentoAndEinvoiceData($apply_detail['order_id'],$apply_id);
					}//if
				}else{//if失败
					$this->sendMagentoAndEinvoiceData($trade_no['order_id'],$trade_no['apply_id'],2);
				}
			}//foreach
			 
		}//if
		//echo "<pre>";print_r($arrRefund);exit();
	}
	
	public function updateCardRefund($apply_id){
		$oRefaccept = &$this->app->model('refund_apply');
        $oOrder = &$this->app->model('orders');
	    $deoObj = &app::get('ome')->model('delivery_order');
		$oRefund = &$this->app->model('refunds');
        $oLoger = &$this->app->model('operation_log');
        $objShop = &$this->app->model('shop');
		
		$apply_detail = $oRefaccept->refund_apply_detail($apply_id);
		if (in_array($apply_detail['status'],array('2','5','6'))){
			$db = kernel::database();
			$transaction_status = $db->beginTransaction();
					
			$order_id = $apply_detail['order_id'];
			$order_detail = $oOrder->order_detail($order_id);
			$ids = $deoObj->getList('delivery_id',array('order_id'=>$order_id));
			//售后申请单处理
			$oretrun_refund_apply = &$this->app->model('return_refund_apply');
			$return_refund_appinfo = $oretrun_refund_apply->dump(array('refund_apply_id'=>$apply_id));
			if ($return_refund_appinfo['return_id'])
			{
				$oreturn = &$this->app->model('return_product');
				$return_info = $oreturn->product_detail($return_refund_appinfo['return_id']);
				if (($return_info['refundmoney']+$apply_detail['money'])>$return_info['tmoney'])
				{
					//$this->end(false, '申请退款金额大于售后的退款金额！');
				}
				$return_info['refundmoney'] = $return_info['refundmoney']+$apply_detail['money'];
	
				if(!$oreturn->save($return_info)){
					 $db->rollback();
					 return false;
				}
	
				$oLoger->write_log('return@ome',$return_info['return_id'],"售后退款成功。");
			}
			 //订单信息更新
			$orderdata = array();
			if (round($apply_detail['money'],3)== round(($order_detail['payed']),3))
			{
				$orderdata['pay_status'] = 5;
			}
			else
			{
				$orderdata['pay_status'] = 4;

			}
			$orderdata['order_id'] =  $apply_detail['order_id'];
			$orderdata['payed'] = $order_detail['payed'] - $apply_detail['money'];//需要将补偿运费减掉
			
			if(!$oOrder->save($orderdata)){
				$db->rollback();
				return false;
			}

            $oLoger->write_log('order_modify@ome',$orderdata['order_id'],$fail_msg."退款成功，更新订单退款金额。");
			
			//退款申请状态更新
			$applydata = array();
			$applydata['apply_id'] = $apply_id;
			$applydata['status'] = 4;//已经退款
			$applydata['refunded'] = $apply_detail['money'];// + $order_detail['payinfo']['cost_payment'];
			$applydata['last_modified'] = time();
			$oRefaccept->save($applydata,true);
			$oLoger->write_log('refund_apply@ome',$applydata['apply_id'],"退款成功，更新退款申请状态。");
			//更新售后退款金额
			$return_id = intval($_POST['return_id']);
			if(!empty($return_id)){
			   $sql = "UPDATE `sdb_ome_return_product` SET `refundmoney`=IFNULL(`refundmoney`,0)+{$apply_detail['money']} WHERE `return_id`='".$return_id."'";
			   kernel::database()->exec($sql);
			}
			 //单据生成：生成退款单
			$refunddata = array();
			$refund_apply_bn = $apply_detail['refund_apply_bn'];
			if ($refund_apply_bn){
				$refund_bn = $refund_apply_bn;
			}else{
				$refund_bn = $oRefund->gen_id();
			}
			$refunddata['refund_bn'] = $refund_bn;
			$refunddata['order_id'] = $apply_detail['order_id'];
			$refunddata['shop_id'] = $order_detail['shop_id'];
			$refunddata['pay_account'] = $apply_detail['pay_account'];
			$refunddata['currency'] = $order_detail['currency'];
			$refunddata['money'] = $apply_detail['money'];
			$refunddata['paycost'] = 0;//没有第三方费用
			$refunddata['cur_money'] = $apply_detail['money'];//汇率计算 TODO:应该为汇率后的金额，暂时是人民币金额
			$refunddata['pay_type'] = $apply_detail['pay_type'];
			$refunddata['payment'] = $apply_detail['payment'];
			$paymethods = ome_payment_type::pay_type();
			$refunddata['paymethod'] = $paymethods[$refunddata['pay_type']];
			//Todo ：确认paymethod
			$opInfo = kernel::single('ome_func')->getDesktopUser();
			$refunddata['op_id'] = $opInfo['op_id'];
	
			$refunddata['t_ready'] = time();
			$refunddata['t_sent'] = time();
			$refunddata['status'] = "succ";#支付状态
			$refunddata['memo'] = $apply_detail['memo'];
			$refunddata['trade_no'] = $apply_detail['wxpaybatchno'];
			if(!$oRefund->save($refunddata)){
				$db->rollback();
				return false;
			}
			
			$oLoger->write_log('refund_accept@ome',$refunddata['refund_id'],"退款成功，生成退款单".$refunddata['refund_bn']);
			if(!empty($return_id)){
			    $return_data = array ('return_id' => $_POST['return_id'], 'status' => '4', 'refundmoney'=>$refunddata['money'], 'last_modified' => time () );
			    $Oreturn_product = $this->app->model('return_product');
			    if(!$Oreturn_product->update_status ( $return_data )){
					$db->rollback();
					return false;
				}
			}
			
			$db->commit($transaction_status);
			
			kernel::single('ome_order_func')->update_order_pay_status($apply_detail['order_id']);
		}
	}
	
	function sendMagentoAndEinvoiceData($order_id,$apply_id,$type='1'){//type:1 succ 2 fail
		
		$objOrder  = kernel::single("ome_mdl_orders");
		$objReship = kernel::single("ome_mdl_reship");

		$arrOrderBn = $objOrder->getList('order_bn,createway,shop_id,ship_status',array('order_id'=>$order_id));
		$arrOrderBn = $arrOrderBn[0];
		
		$createway = $arrOrderBn['createway'];
		$order_bn  = $arrOrderBn['order_bn'];
		
		if($createway == "after"){
			$arrOriginalOrder = $objReship->getOriginalOrder($order_bn);
			$order_bn = $arrOriginalOrder['relate_order_bn']; // 老订单号
			$order_id = $arrOriginalOrder['relate_order_id']; // 老订单号
		}
		
		$magento_type = NULL;

		if($type == "1"){
			$magento_type = 'refund_complete';
			kernel::single('einvoice_request_invoice')->invoice_request($order_id,'getApplyInvoiceData');

            ###### 订单状态回传kafka august.yao 已退款/已取消 start####

            // 判断订单是否发货----已支付未发货订单退款完成推送已取消状态
            if($arrOrderBn['ship_status'] == '0'){
                $status      = 'cancel';
                $queue_title = '订单已取消状态推送';
            }else{
                $status      = 'refunded';
                $queue_title = '订单已退款状态推送';
            }

            $kafkaQueue = app::get('ome')->model('kafka_queue');
            $moneyRes   = app::get('ome')->model('refunds')->dump(array('order_id'=>$order_id),'refund_bn,money');
            if(empty($moneyRes['refund_bn'])){
                $refundBn = app::get('ome')->model('refund_apply')->dump(array('order_id'=>$order_id),'refund_apply_bn');
                $moneyRes['refund_bn'] = $refundBn['refund_apply_bn'];
            }
			$queueData = array(
                'queue_title' => $queue_title,
                'worker'      => 'ome_kafka_api.sendOrderStatus',
                'start_time'  => time(),
                'params'      => array(
                    'status'   => $status,
                    'order_bn' => $order_bn,
                    'logi_bn'  => '',
                    'shop_id'  => $arrOrderBn['shop_id'],
                    'item_info'=> array(),
                    'bill_info'=> array(
                        array(
                            'bn'    => $moneyRes['refund_bn'],
                            'money' => $moneyRes['money'],
                        ),
                    ),
                ),
            );
            $kafkaQueue->save($queueData);
            ###### 订单状态回传kafka august.yao 已退款/已取消 end ####

		}else{
			$magento_type = 'refund_failed';
		}
		kernel::single('omemagento_service_order')->update_status($order_bn, $magento_type);
		$this->sendRefundStatus($apply_id, $type);
	}
	
	function sendRefundToM($refund_id,$order_id,$z_money,$oms_refund_rma_ids=''){
		$data['refund_id']=$refund_id;
		$data['order_id']=$order_id;
		$data['refund_amount']=$z_money;
		$data['refund_status']=0;
		$data['oms_refund_rma_ids']=$oms_refund_rma_ids;
		
		kernel::single('omemagento_service_request')->do_request('refundlog',$data);
	}
	
	function sendRefundStatus($refund_id,$status){
		$data['refund_id']=$refund_id;
		$data['refund_status']=$status;
		
		kernel::single('omemagento_service_request')->do_request('refundlog',$data);
	}
	
    /**
     * 补偿费用显示
     * @param int
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function modifier_bcmoney($row)
    {
        if ($row>0) {
            $bcmoney = sprintf("<div style='background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", 'red', $row, $row, $row);
            return $bcmoney;
        }
    }
}
?>