<?php
class ome_ctl_admin_return_sv extends desktop_controller {

    var $name = "退换货服务";
    var $workground = "aftersale_center";

    function index(){
        $oBranch = &app::get('ome')->model('branch');
        $this->pagedata['error_msg'] = '';
        $wms_id = kernel::single('wms_branch')->getBranchByselfwms();

        $branch_list = $oBranch->getList('branch_id', array('wms_id'=>$wms_id), 0, -1);

        if ($branch_list)
        $branch_ids = array();
        foreach ($branch_list as $branch_list) {
            $branch_ids[] = $branch_list['branch_id'];

        }
        if($_POST['logi_no']){

            $oReship = $this->app->model('reship');
            $reship_id = $oReship->getLogiInfo($_POST['logi_no'],$branch_ids);

            if($reship_id){
               $return = $this->edit($reship_id,'index');
               if ($return !== false) {
                   exit;
               }
            }else{
               $this->pagedata['error_msg'] = '没有找到对应的物流单号';
            }
        }
        
        
        $this->pagedata['count'] = $this->app->model('reship')->count(array('is_check'=>array('3','1'),'branch_id'=>$branch_ids));

        $this->page("admin/return_product/sv/process_check_index.html");
    }

    /**
     * 质检
     *
     * @param Int $reship_id  退货单ID
     * @param String $from_type index:通过物流单质检，‘’:通过鼠标点击质检
     * @return void
     * @author
     **/
    function edit($reship_id,$from_type='')
    {
		@ini_set('memory_limit','512M');
        # 先进行收货处理 判断是否已经收货
        $is_check = $this->app->model('reship')->select()->columns('is_check')->where('reship_id=?',$reship_id)->instance()->fetch_one();
        if ($is_check == '1') {

            kernel::single('ome_return_rchange')->accept_returned($reship_id,'3',$error_msg);
        }

        switch ($is_check) {
            case '0':
                $error_msg = '售后服务单未审核!';
                break;
            case '2':
                $error_msg = '售后服务单审核失败!';
                break;
            case '4':
                $error_msg = '拒绝收货!';
                break;
            case '5':
                $error_msg = '售后服务单审核被拒绝!';
                break;
            case '7':
                $error_msg = '售后服务单审核已经完成!';
                break;
            case '8':
                $error_msg = '质检已经通过!';
                break;
            case '9':
                $error_msg = '质检已经被拒绝!';
                break;
            default:
                # code...
                break;
        }

        if ($error_msg) {
            if ($from_type == 'index') {
                $this->pagedata['error_msg'] = $error_msg;
                return false;
            }
        }

        $oProduct_pro = &$this->app->model('return_process');
        $oOrder = &$this->app->model('orders');
        $oProblem = &$this->app->model('return_product_problem');
        $productObj = &$this->app->model('products');
        $goodsObj = &$this->app->model('goods');
        $productSerialObj = &$this->app->model('product_serial');
        $oSeriallog = &$this->app->model('product_serial_log');
        $memo='';
        $serial['merge'] = $this->app->getConf('ome.product.serial.merge');
        $serial['separate'] = $this->app->getConf('ome.product.serial.separate');
        
        $oBranch = &app::get('ome')->model('branch');
        $orders = $oProduct_pro->dump(array('reship_id'=>$reship_id),'order_id,memo');
        $oProduct_pro_detail = $oProduct_pro->product_detail($reship_id,$orders['order_id']);

        $order_id = $orders['order_id'];
        $delivery_order = $oOrder->db->selectrow("SELECT *
                FROM sdb_ome_delivery_order as deo
                LEFT JOIN sdb_ome_delivery AS d ON deo.delivery_id = d.delivery_id
                WHERE deo.order_id={$order_id}
                AND (d.parent_id=0 OR d.is_bind='true')");
        
        #wms自有仓库_重新获取发货单信息 ExBOY
        $branchLib = kernel::single('ome_branch');
        $channelLib = kernel::single('channel_func');
        
        $wms_id    = $branchLib->getWmsIdById($delivery_order['branch_id']);
        $is_selfWms = $channelLib->isSelfWms($wms_id);//是否自有仓储
        if($is_selfWms)
        {
            $wmsDelivery     = app::get('wms')->model('delivery');
            $wms_delivery    = $wmsDelivery->dump(array('outer_delivery_bn'=>$delivery_order['delivery_bn']), 'delivery_id, delivery_bn');
            
            $delivery_order    = array_merge($delivery_order, $wms_delivery);
        }
        
        $reason = unserialize($oProduct_pro_detail['reason']);

        $oProduct_pro_detail['memo'] = $reason['check'];
        $wms_id = kernel::single('wms_branch')->getBranchByselfwms();
        $isExistOfflineBranch = $oBranch->isExistOfflineBranchBywms($wms_id);

        # 如果没有线下仓 去除残仓、报仓
        if (!$isExistOfflineBranch) {
            unset($oProduct_pro_detail['StockType'][1],$oProduct_pro_detail['StockType'][2]);
        }
        $isExistOnlineBranch = $oBranch->isExistOnlineBranchBywms($wms_id);
        # 如果没有线上仓 去除新仓
        if (!$isExistOnlineBranch) {
            unset($oProduct_pro_detail['StockType'][0]);
        }

        $forNum = array();
        $serial_numbers = array();
        $mixed_array = array();

		$bnArr = array();
		$gArr = array();
		$serialLogArr = array();
		$serialProductArr = array();
        $product_process = $oProduct_pro_detail;
        unset($product_process['items']);
        //$product_process['items'] = array();
        foreach ($oProduct_pro_detail['items'] as $key => $val){
            if($val['return_type'] == 'change'){
                unset($oProduct_pro_detail['items'][$key]);
                break;
            }

			if (!isset($bnArr[$val['bn']])) {
                 $bnArr[$val['bn']] = $productObj->dump(array('bn'=>$val['bn']), 'goods_id,barcode,spec_info');;
			}
            //$p = $productObj->dump(array('bn'=>$val['bn']), 'goods_id,barcode,spec_info');
			$p = $bnArr[$val['bn']];
            if (!isset($gArr[$p['goods_id']])) {
                 $gArr[$p['goods_id']] = $goodsObj->dump($p['goods_id'], 'serial_number');
			}
            //$g = $goodsObj->dump($p['goods_id'], 'serial_number');
			$g = $gArr[$p['goods_id']];

            $mixed_array['bn_'.$val['bn']] = $val['bn'];
            /*唯一码是否存在*/
            if($g['serial_number'] != 'false' || $g['serial_number'] != NULL){
               //$serial_product = $productSerialObj->dump(array('bn'=>$val['bn'],''),'serial_number');
			   $md5 = md5($val['bn'] . $delivery_order['delivery_id']);
               if (!isset($serialLogArr[$md5])) {
                     $serialLogArr[$md5] = $oSeriallog->db->selectrow("SELECT s.item_id FROM sdb_ome_product_serial as s LEFT JOIN sdb_ome_product_serial_log as l ON s.item_id=l.item_id WHERE s.bn='".$val['bn']."' AND l.bill_no=".$delivery_order['delivery_id']." AND s.status in('1')");
			   }
                //$serial_log = $oSeriallog->db->selectrow("SELECT s.item_id FROM sdb_ome_product_serial as s LEFT JOIN sdb_ome_product_serial_log as l ON s.item_id=l.item_id WHERE s.bn='".$val['bn']."' AND //l.bill_no=".$delivery_order['delivery_id']." AND s.status in('1')");
			    $serial_log = $serialLogArr[$md5];

               if (!isset($serialProductArr[$serial_log['item_id']])) {
				   $serialProductArr[$serial_log['item_id']] = $productSerialObj->dump(array('item_id'=>$serial_log['item_id']),'serial_number');
			   }
               //$serial_product = $productSerialObj->dump(array('item_id'=>$serial_log['item_id']),'serial_number');
			   $serial_product = $serialProductArr[$serial_log['item_id']];
               $serial_numbers[][$serial_product['serial_number']] = $val['bn'];
               $mixed_array['serial_number_'.$serial_product['serial_number']] = $val['bn'];

            }
            //判断条形码是否为空
            if(!empty($p['barcode'])){
               $mixed_array['barcode_'.$p['barcode']] = $val['bn'];
            }

            /* 退货数量 */
            if($product_process['items'][$val['bn']]){
                $product_process['items'][$val['bn']]['num'] += $val['num'];
            }else{
                $product_process['items'][$val['bn']] = $val;
            }

            if(!empty($serial_product['serial_number'])){
               $product_process['items'][$val['bn']]['serial_number'] = $serial_product['serial_number'];
            }

            $product_process['items'][$val['bn']]['barcode'] = $p['barcode'];

            /* 校验数量 */
            if($val['is_check'] == 'true'){
                $product_process['items'][$val['bn']]['checknum'] += $val['num'];
                $oProduct_pro_detail['items'][$key]['checknum'] = $val['num'];
            }

            $product_process['items'][$val['bn']]['itemIds'][] = $val['item_id'];

            if($val['is_check'] == 'false'){
                /* 退货数量 */
                if($forNum[$val['bn']]){
                    $forNum[$val['bn']] += 1;
                    $oProduct_pro_detail['items'][$key]['fornum'] = $forNum[$val['bn']];
                }else{
                    $oProduct_pro_detail['items'][$key]['fornum'] = 1;
                    $forNum[$val['bn']] = 1;
                }
            }
            $product_process['items'][$val['bn']]['spec_info'] = $p['spec_info'];
            unset($oProduct_pro_detail['items'][$key]);
            $product_process['por_id'] = $val['por_id'];
        }
        
        $serial['serial_numbers'] = $serial_numbers;

        $list = $oProblem->getList('problem_id,problem_name');
        $product_process['problem_type'] = $list;

        $this->pagedata['pro_detail']=$product_process;
        $return_apply = $this->app->model('return_product')->getList('*',array('return_id'=>$oProduct_pro_detail['return_id']));
        $this->pagedata['return_apply'] = $return_apply[0];
        #
        $plugin_html = '';
        
        if ($return_apply[0]['source']=='matrix') {
          $plugin_html = kernel::single('ome_aftersale_service')->reship_edit($return_apply[0]);
        }
        $this->pagedata['plugin_html'] = $plugin_html;
        #
        $this->pagedata['mixed_array'] = json_encode($mixed_array);
        if (!is_numeric($oProduct_pro_detail['attachment'])){
            $this->pagedata['attachment_type'] = 'remote';
        }
        $oReship = &$this->app->model('reship');
        $Orship_items = $oReship->getReshipItems($reship_id);
        $this->pagedata['order'] = $Orship_items;
        $this->pagedata['serial'] = $serial;

		unset($bnArr);
		unset($gArr);
		unset($serialLogArr);
		unset($serialProductArr);

        if($from_type == 'index'){//1231321
            $this->pagedata['from_type'] = $from_type;
            $this->page("admin/return_product/sv/edit.html");
        }else{
            $this->display("admin/return_product/sv/edit.html");
        }


    }

    /**
     * 保存质检信息
     *
     * @param Int $reship_id 退换单据ID
     * @param Int $status 8:质检通过 9:拒绝质检
     * @return void
     * @author
     **/
    function tosave($reship_id,$status)
    {
        set_time_limit(0);
        if($_POST['from_type'] == 'index'){
           $this->begin();
        }else{
           $this->begin('index.php?app=wms&ctl=admin_return_rchange&act=index&flt=process_list');
        }

        $oProduct_pro = &$this->app->model('return_process');
        $oProduct = &$this->app->model('return_product');
        $oReship = &$this->app->model('reship');
        $oProblem_type = &$this->app->model('return_product_problem_type');
        $oProblem = &$this->app->model('return_product_problem');
        $oBranch = &app::get('ome')->model('branch');
        $productSerialObj = &$this->app->model('product_serial');
        $serialLogObj = &$this->app->model('product_serial_log');
        $oOperation_log = &$this->app->model('operation_log');//写日志
        $pro_items =&$this->app->model('return_process_items');
        $reship  = &$this->app->model('reship_items');

        switch($status){
            case '9' :   # 拒绝质检
                # 拒检原因
                $reason = $oReship->select()->columns('reason')->where('reship_id=?',$reship_id)->instance()->fetch_one();
                $refuseMemo = unserialize($reason);
                //$refuseMemo .= '#质检原因#'.$_POST['info']['refuse_memo'];
                $refuseMemo['refuse'] = $_POST['info']['refuse_memo'];
				$reship = $oReship->dump($reship_id,'is_check,return_type,order_id,m_reship_bn');
				
                $refuse = array(
                    #'is_check' => '9',
                    'reason' => serialize($refuseMemo),
                );
				if($reship['return_type']=="change"){//换货直接取消
					$refuse['is_check'] = '5';
					$refuse['t_end'] = time();
				}else{
					if ($reship['is_check'] == '13'){#有过收货记录置拒绝
						
						$refuse['is_check'] = '9';
					}else{#否则异常
						$refuse['is_check'] = '12';
					}
				}
				
                $reship_result = $oReship->update($refuse,array('reship_id'=>$reship_id));
				if ($reship_result){
					#如果为异常，删除收货记录
					if ($refuse['is_check']=='12'){
						$oProduct_pro->delete(array('reship_id'=>$reship_id));
						$pro_items->delete(array('reship_id'=>$reship_id));
					}
					
                    //释放库存拒绝时
                    if ($reship['return_type'] == 'change') {
                        kernel::single('console_reship')->change_freezeproduct($reship_id,'-');
						//拒绝后通知magento
						$objOrder=&$this->app->model("orders");
						$arrOrder=$data=array();
						$arrOrder=$objOrder->getList("order_bn",array('order_id'=>$reship['order_id']));
						$data['order_bn']=$arrOrder[0]['order_bn'];
						$data['exchange_no']=$reship['m_reship_bn'];
						$data['status']='failed';
						$data['admin_comment']=$refuseMemo['refuse'];
						kernel::single('omemagento_service_change')->updateStatus($data);
                    }
				}
                # 写LOG
                $oOperation_log->write_log('reship@ome',$reship_id,'拒绝质检');

                $this->end(true,$this->app->_('拒绝质检成功'));
                break;

            case '10':
                        $filter = array('reship_id'=>$reship_id);
                        $reshipinfo = $oReship->getList('memo',$filter,0,1);
                        $memo = $reshipinfo[0]['memo'].'！'.$_POST['process_memo'];
                        $oReship->update(array('is_check'=>'10','memo'=>$memo),$filter);
                        $oOperation_log->write_log('reship@ome',$reship_id,'质检异常');
                        $this->end(true,$this->app->_('质检异常更新成功!'));
            case '8' :   # 质检

                if(empty($_POST['process_id'])) $this->end(false,$this->app->_('请先扫描货号/条形码'));

                $opInfo = kernel::single('ome_func')->getDesktopUser();

                $row = $oReship->getList('reship_id',array('reship_id'=>$reship_id,'is_check'=>array('1','3','13')));
                if (!$row) {
                    $this->end(false,$this->app->_('退换货单据未审核!'));
                }

                if (!$_POST['por_id']) {
                    $this->end(false,$this->app->_('请选择质检明细!'));
                }

                # 已经扫描的货号
                $scans = $_POST['process_id'];

                # 验证可质检数
                $checknum = $noscan = array();
                foreach ($scans as $key=>$pbn) {
                    if($key<=0) continue;
                    # 可质检数
                    if (!$noscan[$pbn]) {
                        $filter  = array(
                            'bn' => $pbn,
                            'por_id' => $_POST['por_id'],
                            'is_check' => 'false',
                        );
                        $row = $pro_items->getList('sum(num) as _s',$filter,0,1);
                        $noscan[$pbn] = $row[0]['_s'];
                    }

                    $checkKey = $pbn.$key;

                    $checknum[$pbn] += $_POST['check_num'][$checkKey];

                    if (!isset($_POST['instock_branch'][$checkKey])) {
                        $this->end(false,$this->app->_('请选择入库类型!'));
                    }
                }
				
                foreach ($noscan as $pbn=>$sum) {
                    if ($checknum[$pbn]!=$sum) {
                        $this->end(false,$this->app->_('请全部扫描完!'));
                        return false;
                    }
                }
                
                #将数据以货号+仓库+备注方式重新组合
                $new_processData = array();
                foreach ( $scans as  $sk=>$sbn) {
                    if($sk<=0) continue;
                    $checkKey = $sbn.$sk;
                    $tmp_branch = intval($_POST['instock_branch'][$checkKey]);
                    $probn = md5($sbn.$tmp_branch.$_POST['memo'][$checkKey]);
                    if (isset($new_processData[$probn])) {
                        $new_processData[$probn]['check_num']+=$_POST['check_num'][$checkKey];
                    }else{
                        $sale_price = $reship->getList('price',array('reship_id'=>$_POST['reship_id'],'bn'=>$sbn));//做售后时，用户输入的价格
                        $new_processData[$probn] = array(
                            'check_num' => $_POST['check_num'][$checkKey],
                            'memo'      => $_POST['memo'][$checkKey],
                            'store_type'=> $_POST['store_type'][$checkKey],
                            'branch_id' => $tmp_branch,
                            'acttime'   => time(),
                            'por_id'    => $_POST['por_id'],
                            'reship_id' => $_POST['reship_id'],
                            'is_check' => 'true',
                            'bn'       => $sbn,
                            'need_money' =>$sale_price[0]['price'],
                        );
                    }
                }
           
                if ($new_processData) {
                    foreach ($new_processData as $processdata ) {
                        $check_num  = $processdata['check_num'];
                        $memo       = $processdata['memo'];
                        $store_type = $processdata['store_type'];
                        $branch_id  = $processdata['branch_id'];
                        $acttime    = $processdata['acttime'];
                        $por_id     = $processdata['por_id'];
                        $reship_id  = $processdata['reship_id'];
                        $bn         = $processdata['bn'];
                        $need_money  = $processdata['need_money'];
                        $SQL = "UPDATE sdb_ome_return_process_items SET 
                                need_money=".$need_money.",
                                memo='$memo',store_type='".$store_type."',branch_id=".$branch_id.",
                                acttime=".$acttime.",is_check='true' WHERE item_id in (SELECT t.item_id FROM
                                (select * from sdb_ome_return_process_items WHERE reship_id=".$reship_id." AND por_id=".$por_id." AND bn='$bn' AND is_check='false' LIMIT 0,".$check_num.") as t )";

                        $pro_items->db->exec($SQL);
                        # 写日志
                        $memo = '有'.$check_num.'件货品【'.$bn.'】质检成功,进入'.$oProblem->get_store_type($store_type).':'.$oBranch->Get_name($branch_id);
                        if($_POST['return_id']){
                           $oOperation_log->write_log('return@ome',$_POST['return_id'],$memo);
                        }
                        $oOperation_log->write_log('reship@ome',$_POST['reship_id'],$memo);
                        #更新退货单上良品数量
                        $branch = $pro_items->db->selectrow('SELECT attr FROM sdb_ome_branch WHERE branch_id='.$branch_id);

                        if ($branch['attr']=='true') {
                            $ship_sql = 'UPDATE sdb_ome_reship_items SET normal_num='.$check_num.' WHERE reship_id='.$reship_id.' AND bn=\''.$bn.'\'';
                        }else{
                            $ship_sql = 'UPDATE sdb_ome_reship_items SET defective_num='.$check_num.' WHERE reship_id='.$reship_id.' AND bn=\''.$bn.'\'';
                        }

                        $pro_items->db->exec($ship_sql);
                    }
                }
                
//                foreach ($scans as $key=>$pbn) {
//                    if($key<=0) continue;
//                    $checkKey = $pbn.$key;
//
//                    $tmp_branch = intval($_POST['instock_branch'][$checkKey]);
//
//
//                    # 单个框中的数量
//                    for ($i=1; $i <= $_POST['check_num'][$checkKey]; $i++) {
//                        # 质检处理明细
//                        $process_items = $pro_items->select()->columns('item_id,product_id')
//                                                    ->where('bn=?',$pbn)
//                                                    ->where('is_check=?','false')
//                                                    ->where('por_id=?',$_POST['por_id'])
//                                                    ->instance()->fetch_row();
//                       if(!$process_items) continue; #已经质检
//
//                       $process_items_update = array(
//                            'item_id' => $process_items['item_id'],
//                            'memo' => $_POST['memo'][$checkKey],
//                            'acttime' => time(),
//                            'op_id' => $opInfo['op_id'],
//                            'is_check' => 'true',
//                            'product_id' => $process_items['product_id'],
//                            'por_id' => $_POST['por_id'],
//                            'reship_id' => $_POST['reship_id'],
//                            'store_type' => $_POST['store_type'][$checkKey],
//                            'branch_id' => $tmp_branch,
//                       );
//                       $pro_items->save($process_items_update);
//
//                    }
//
//                    # 写日志
//                    $memo = '有'.$_POST['check_num'][$checkKey].'件货品【'.$pbn.'】质检成功,进入'.$oProblem->get_store_type($_POST['store_type'][$checkKey]).':'.$oBranch->Get_name($tmp_branch);
//                    if($_POST['return_id']){
//                       $oOperation_log->write_log('return@ome',$_POST['return_id'],$memo);
//                    }
//                    $oOperation_log->write_log('reship@ome',$_POST['reship_id'],$memo);
//                }

                # 唯一码
                if($_POST['serial_id']){
                    
                    foreach($_POST['serial_id'] as $val){
                        $serialData = $productSerialObj->dump(array('serial_number'=>$val));
                        if($serialData && $serialData['item_id']>0){
                            $stock_type_serial = array_shift($_POST['store_type']);
                            $serialData['status'] = ($stock_type_serial>0) ? 2 : 0;
                            $productSerialObj->save($serialData);

                            $logData['item_id'] = $serialData['item_id'];
                            $logData['act_type'] = 1;
                            $logData['act_time'] = time();
                            $logData['act_owner'] = $opInfo['op_id'];
                            $logData['bill_type'] = 1;
                            $logData['bill_no'] = $_POST['reship_id'];
                            
                            $logData['serial_status'] = $serialData['status'];
 
                            $serialLogObj->save($logData);
                            
                            unset($serialData,$logData);
                        }
                    }
                }

                $return_id=$_POST['return_id'];
                $oProduct_pro->changeverify($_POST['por_id'],$_POST['reship_id'],$return_id,$_POST['process_memo']);
				
                $return_product_detail = $oProduct_pro->dump(array('por_id'=>$_POST['por_id']), 'verify');
				
                # 质检成功后的操作
                if ($return_product_detail['verify'] == 'true'){
                    #质检入库

                    if(!kernel::single('ome_return_process')->do_iostock($_POST['por_id'],1,$msg)){
                        $url = 'index.php?app=ome&ctl=admin_return_sv&act=edit&p[0]='.$_POST['reship_id'];
                        $this->end(false, $this->app->_('质检入库失败'), $url , array('msg'=>$msg));
                    }

                    //质检成功后，根据退换货类型生成相应的单据
                    if ($status == '8') {
                        $oReship->finish_aftersale($_POST['reship_id']);
                    }else if($return_id){
                        //售后申请状态更新同步
                        foreach(kernel::servicelist('service.aftersale') as $object=>$instance){
                            if(method_exists($instance,'update_status')){
                                $instance->update_status($return_id);
                            }
                        }
                        
                        
                    }

                    # 更新状态
                    /*
                    if ($status == '10') {
                        $oReship->update(array('is_check'=>'10'),array('reship_id'=>$reship_id));
                        $oOperation_log->write_log('reship@ome',$_POST['reship_id'],'质检异常');
                        $this->end(true,$this->app->_('质检异常更新成功!'));
                    }*/
                }

                $this->end(true, app::get('base')->_('质检成功'));
                break;
            default:

                $this->end(true, app::get('base')->_('质检失败'));
                break;
        }

    }

    /**
     * @description 重新质检
     * @access public
     * @param void
     * @return void
     */
    public function recheck($reship_id)
    {
        $this->begin();
        if (!$reship_id) {
            $this->end(false,$this->app->_('退换货单不能为空!'));
        }

        # 删除收货记录
        $this->app->model('return_process')->delete(array('reship_id'=>$reship_id));
        $this->app->model('return_process_items')->delete(array('reship_id'=>$reship_id));

        # 更改状态
        $this->app->model('reship')->update(array('is_check'=>'1'),array('reship_id'=>$reship_id));

        # 删除操作记录
        //$this->app->model('operation_log')->delete(array('obj_type' => 'reship@ome','obj_id'=>$reship_id));
        $this->app->model('operation_log')->write_log('reship@ome',$reship_id,'重新质检');

        $this->end(true,$this->app->_('退换货单不能为空!'),'javascript:finderGroup["'.$_GET['finder_id'].'"].refresh.delay(400,finderGroup["'.$_GET['finder_id'].'"]);');
    }
}
