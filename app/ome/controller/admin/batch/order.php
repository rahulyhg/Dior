<?php
class ome_ctl_admin_batch_order extends desktop_controller{
    var $name = "订单中心";
    var $workground = "order_center";

    
    /**
     * 批量审核订单
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function batchConfirm()
    {

        $corpObj      = app::get('ome')->model('dly_corp');
        $branchObj  = app::get('ome')->model('branch');
        $orderObj    = app::get('ome')->model('orders');
        $branchList = $branchObj->db->select('SELECT branch_id,name FROM sdb_ome_branch WHERE disabled=\'false\' AND is_deliv_branch=\'true\'');
        $arrBranchId[] = 0;
        foreach($branchList as $val){
            $arrBranchId[] = $val['branch_id'];
        }
        $corpList = $corpObj->getList('branch_id, all_branch, corp_id, name, type, is_cod, weight, channel_id, shop_id, tmpl_type', array('disabled' => 'false', 'branch_id' => $arrBranchId), 0, -1, 'weight DESC');
        $order_ids = $_POST['order_id'];
       
        $orders = $orderObj->getList('order_id',array('order_id'=>$order_ids,'process_status'=>array('unconfirmed','confirmed'),'order_confirm_filter' => '((is_cod=\'true\' or pay_status in (\'1\',\'4\',\'5\')))', 'status' => 'active', 'ship_status' => '0', 'f_ship_status' => '0',  'abnormal' => 'false', 'refund_status' => 0, 'is_auto' => 'false', 'is_fail' => 'false','pause'=>'false'));
        $orderGroup = array();
        foreach ( $orders as $order ) {
            $orderGroup[] = $order['order_id'];
        }
        $this->pagedata['finder_id'] = $_GET['finder_id'];
         $this->pagedata['ordercount'] = count($orderGroup);
        $this->pagedata['orderGroup'] = json_encode($orderGroup);
        $this->pagedata['currentTime'] = time();
        $this->pagedata['ordertotal'] = count($order_ids);
        $this->pagedata['corpList'] = $corpList;
        $this->pagedata['branchList'] = $branchList;
        unset($corpList,$branchList,$orderGroup);

        $this->display('admin/batch/confirm.html');
    }

    
    /**
     * 自动审核订单.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function ajaxDoAuto()
    {
        $combineObj = kernel::single('omeauto_auto_combine');
        $oOrder = app::get('ome')->model('orders');
        $batch_orderObj = kernel::single('ome_batch_order');
        $group = kernel::single('omeauto_auto_group_item');
        $branch_id = $_POST['branch_id'];
        $corp_id = $_POST['corp_id'];
        $corps = app::get('ome')->model('dly_corp')->dump($corp_id, 'corp_id, name, type, is_cod, weight');
        $result = array('total' => 0, 'succ' => 0, 'fail' => 0);
        $ajaxParams = explode(';',$_POST['ajaxParams']);
        $rows = $oOrder->getList('*', array('order_id' => $ajaxParams));
        $orders = array();
        foreach ($rows as $order) {
            $orders[$order['order_id']] = $order;
            
        }
        unset($rows);
        foreach ( $orders as $order ) {
            $order_id = $order['order_id'];
            $result['total'] ++;
            $consignee['branch_id'] = $branch_id;
            //验证库存
            $product_store = $batch_orderObj->getBranchStore($order_id,$branch_id);
            //验证到不到
            $arrived = $batch_orderObj->get_arrived($order,$corps);

            if ($product_store && $arrived) {
                $deliveryresult =  $combineObj->mkDelivery($order_id, $consignee, $corp_id);
            
                if ($deliveryresult) {
                    $result['succ'] ++;
                }else{
                    $result['fail']++;
                }
            }else{
                $msgFlag = array();
                if ($orders['auto_status']>0) {
                    $msgFlag[] =$orders['auto_status'];
                }
                
                
                if (!$arrived) {
                    $msgFlag[] = omeauto_auto_const::_LOGIST_ARRIVED;
                }
                if (!$product_store) {
                    $msgFlag[] = omeauto_auto_const::__STORE_CODE;
                }
                
                $auto_status =implode('|',$msgFlag);

                $oOrder->db->exec("UPDATE sdb_ome_orders SET auto_status='".$auto_status."' WHERE order_id=".$order_id);
                $result['fail']++;
            }
            
        }
        echo json_encode($result,true);
    }


    
   
    
 
}
?>
