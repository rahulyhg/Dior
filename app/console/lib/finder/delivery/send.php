<?php
class console_finder_delivery_send{
   var $detail_basic = "发货单详情";
   function detail_basic($dly_id){
        $render = app::get('ome')->render();
        $dlyObj = &app::get('ome')->model('delivery');
        $orderObj = &app::get('ome')->model('orders');
        $braObj = &app::get('ome')->model('branch');
        $opObj  = &app::get('ome')->model('operation_log');
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $dly = $dlyObj->dump($dly_id);
        $tmp = app::get('ome')->model('members')->dump($dly['member_id']);
        $dly['member_name'] = $tmp['account']['uname'];
        $dly['members'] = "手机：".$tmp['contact']['phone']['mobile']."<br>";
        $dly['members'] .= "电话：".$tmp['contact']['phone']['telephone']."<br>";
        empty($dly['branch_id'])?$branch_id=0:$branch_id=$dly['branch_id'];
        $shop = $dlyObj->getShopInfo($dly['shop_id']);
        $dly['area'] = $shop['area'];

        $orderIds = $dlyObj->getOrderIdByDeliveryId($dly_id);
        /*$sql = "SELECT dc.* FROM sdb_ome_branch_area ba
                                LEFT JOIN sdb_ome_dly_corp_area dca
                                    ON ba.region_id=dca.region_id
                                LEFT JOIN sdb_ome_dly_corp  dc
                                    ON dca.corp_id=dc.corp_id WHERE ba.branch_id='$branch_id'";*/
        if ($orderIds)
        $ids = implode(',', $orderIds);
        if ($orderIds)
        foreach ($orderIds as $oid)
        {
            $order = $orderObj->dump($oid);
            $order_bn[] = $order['order_bn'];
        }

        /* 发货单日志 */
        $logdata = $opObj->read_log(array('obj_id'=>$dly_id,'obj_type'=>'delivery@ome'), 0, -1);
		foreach($logdata as $k=>$v){
			$logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
		}

        /* 同批处理的订单日志 */
        $order_ids = $dlyObj->getOrderIdByDeliveryId($dly_id);
        $orderLogs = array();
        foreach($order_ids as $v){
            $order = $orderObj->dump($v,'order_id,order_bn');
            $orderLogs[$order['order_bn']] = $opObj->read_log(array('obj_id'=>$v,'obj_type'=>'orders@ome'), 0, -1);
            foreach($orderLogs[$order['order_bn']] as $k=>$v){
                if($v)
                    $orderLogs[$order['order_bn']][$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
            }
        }

        $dlyorderObj = &app::get('ome')->model('delivery_order');
        #根据物流单号，获取会员备注与订单备注
        $markInfo = $dlyorderObj->getMarkInfo($dly_id);
        $custom_mark = array();#会员备注
        $mark_text = array();#订单备注
        foreach($markInfo as $key=>$v){
            $custom_mark[$v['order_bn']] = kernel::single('ome_func')->format_memo($v['custom_mark']);
            $mark_text[$v['order_bn']] = kernel::single('ome_func')->format_memo($v['mark_text']);
        
        }
        $render->pagedata['custom_mark'] = $custom_mark;#会员备注与订单备注信息
        $render->pagedata['mark_text'] = $mark_text;#会员备注与订单备注信息  
        $render->pagedata['write']    = $this->write;
        $dlyCorp = $dlyCorpObj->dump($dly['logi_id'], 'prt_tmpl_id,type,tmpl_type');
        //物流公司使用电子面单时物流单号不能被编辑
        
            $render->pagedata['write'] = 1;
       
        $render->pagedata['url']    = $this->url;
        $render->pagedata['log']      = $logdata;
        $render->pagedata['orderLogs'] = $orderLogs;
        $render->pagedata['dly_corp'] = $braObj->get_corp($branch_id,$dly['consignee']['area']);//$dlyObj->db->select($sql);
		$dly['create_time'] = date('Y-m-d H:i:s',$dly['create_time']);
        $render->pagedata['dly']      = $dly;
        $render->pagedata['order_bn'] = $order_bn;
        //echo "<pre>";
        //print_r($render->pagedata['dly']);die;
        $render->pagedata['status'] = $_GET['status'];
        

        return $render->fetch('admin/delivery/delivery_detail.html');
    }
}