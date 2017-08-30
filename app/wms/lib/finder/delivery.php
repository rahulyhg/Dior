<?php
class wms_finder_delivery{
    var $detail_basic = "发货单详情";
    var $detail_item = "货品详情";
	var $detail_delivery = "物流单列表";
    private $write = '1';
    private $write_memo = '1';
    var $has_many = array(
       'members' => 'members',
    );

    var $addon_cols = "skuNum,itemNum,bnsContent,delivery_id,status,process_status,print_status,type,bind_key,order_createtime,deli_cfg,is_cod,outer_delivery_bn";

    function __construct(){
        if($_GET['ctl'] == 'admin_receipts_print' && $_GET['act'] == 'index'){
            $this->write = '2';
            $this->write_memo = '2';
            $this->url = 'admin_receipts_print';
        }elseif($_GET['ctl'] == 'admin_refunded' && $_GET['act'] == 'index'){
            $this->write = '2';
            $this->write_memo = '2';
            $this->url = 'admin_refunded';
        }else{
           unset($this->column_op);
        }
    }

    //单据状态
    var $column_status = "单据状态";
    var $column_status_width = "80";
    function column_status($row){
        $status = $row[$this->col_prefix.'status'];
        switch($status){
            case 0:
                return '处理中';
                break;
            case 1:
                return '取消';
                break;
            case 2:
                return '暂停';
                break;
            case 3:
                return '已完成';
                break;
        }
    }

    //处理状态
    var $column_process_status = "处理状态";
    var $column_process_status_width = "80";
    function column_process_status($row){
        $process_status  = $row[$this->col_prefix.'process_status'];
        $tmp_status = '未打印';
        if(($process_status & 1) == 1){
            $tmp_status = '已打印';
        }
        if(($process_status & 2) == 2){
            $tmp_status = '已校验';
        }
        if(($process_status & 4) == 4){
            $tmp_status = '已称重打包';
        }
        if(($process_status & 8) == 8){
            $tmp_status = '已物流交接';
        }
        return $tmp_status;
    }

    //打印状态
    var $column_print_status = "打印状态";
    var $column_print_status_width = "80";
    function column_print_status($row){
        $print_status  = $row[$this->col_prefix.'print_status'];
        $stock = false;
        $deliv = false;
        $expre = false;
        if(($print_status & 1) == 1){
            $stock = true;
        }
        if(($print_status & 2) == 2){
            $deliv = true;
        }
        if(($print_status & 4) == 4){
            $expre = true;
        }

        $stockColor = ($stock == 'true') ? 'green' : '#eeeeee';
        $delivColor = ($deliv == 'true') ? 'red' : '#eeeeee';
        $expreColor = ($expre == 'true') ? '#9a6913' : '#eeeeee';
        $ret = $this->getViewPanel('备货单', $stockColor);
        $ret .= $this->getViewPanel('发货单', $delivColor);
        $ret .= $this->getViewPanel('快递单', $expreColor);
        return $ret;
    }

    private function getViewPanel($caption, $color) {
        if ($color == '#eeeeee')
            $caption .= '未打印';
        else
            $caption .= '已打印';
        return sprintf("<div style='width:18px;padding:2px;height:16px;background-color:%s;float:left;'><span alt='%s' title='%s' style='color:#eeeeee;'>&nbsp;%s&nbsp;</span></div>", $color, $caption, $caption, substr($caption, 0, 3));
    }

    var $column_create = "下单距今";
    var $column_create_width = "100";
    var $column_create_order_field= 'order_createtime';
    function column_create($row) {
        $time = $row[$this->col_prefix . 'order_createtime'];
        $difftime = kernel::single('ome_func')->toTimeDiff(time(), $time);
        $status = $row[$this->col_prefix . 'status'];
        $days = $difftime['d'];
        $html .= $difftime['d']?$difftime['d']. '天':'';
        $html .= $difftime['h']?$difftime['h'] . '小时':'';
        $html .= $difftime['m']?$difftime['m'] . '分':'';
        if ($status != 3) {
            if ($days >= 7) {
                $ret = "<div style='width:90px;height:20px;background-color:red;color:#FFFFFF;text-align:center;'>超过一周</div>";
            } elseif ($days >= 1) {
                $ret = "<div style='width:90px;height:20px;background-color:blue;color:#FFFFFF;text-align:center;'>" . $html . "</div>";
            } else {
                $ret = "<div style='width:90px;height:20px;background-color:green;color:#FFFFFF;text-align:center;'>" . $html . "</div>";
            }
        } else {
            $ret = "<div style='width:90px;height:20px;background-color:#dddddd;color:#FFFFFF;text-align:center;'>完成</div>";
        }
        return $ret;
    }

    var $column_beartime = "成单时间";
    var $column_beartime_width = '140';
    var $column_beartime_order_field= 'order_createtime';
    public function column_beartime($row) {
        return $row[$this->col_prefix . 'order_createtime'] ? date('Y-m-d H:i:s',$row[$this->col_prefix . 'order_createtime']) : '-';
    }

    var $column_content = "订单内容";
    var $column_content_width = "160";
    function column_content($row) {
        $skuNum = $row[$this->col_prefix . 'skuNum'];
        $itemNum = $row[$this->col_prefix . 'itemNum'];
        $content = $row[$this->col_prefix . 'bnsContent'];

        $cnts = unserialize($content);
        $cnt = sprintf("共有 %d 种商品，总共数量为 %d 件， 具体 SKU 为： %s", $skuNum, $itemNum, @implode(', ', $cnts));

        @reset($cnts);
        $content = $cnts[@key($cnts)];
        if ($skuNum >1) {
            $content .= ' 等';
        }
        return sprintf("<span alt='%s' title='%s'><font color='red'>(%d / %d)</font> %s</span>",$cnt, $cnt, $skuNum, $itemNum, $content);
    }

	var $column_deliveryNumInfo = "物流单号-多包";
    var $column_deliveryNumInfo_width = "160";
	function column_deliveryNumInfo($dly_id){
    	$dlyObj = &app::get('wms')->model('delivery');
    	$deliveryBillObj = &app::get('wms')->model('delivery_bill');
		$delivery = $dlyObj->dump($dly_id['delivery_id']);
		$deliveryBillInfo = $deliveryBillObj->dump(array('delivery_id'=>$dly_id['delivery_id'],'type'=>1),'logi_no');
		if($delivery['logi_number']>1){
			$str="共有 ".$delivery['logi_number']." 单物流单，已完成发货 ".$delivery['delivery_logi_number']." 单 主物流单号为 ".$deliveryBillInfo['logi_no'];

			return  '<span title="'.$str.'" alt="'.$str.'"><font color="red">('.$delivery['delivery_logi_number'].' / '.$delivery['logi_number'].')</font> '.$deliveryBillInfo['logi_no'].'</span>';
		}else{
			return $deliveryBillInfo['logi_no'];
		}
    }

    function row_style($row){
        $style='';
        if($row[$this->col_prefix.'is_cod'] == 'true'){
            $style .= " list-even ";
        }
        return $style;
    }

    function detail_basic($dly_id){
        $render = app::get('wms')->render();
        $dlyObj = &app::get('wms')->model('delivery');
        $dlyBillLib = kernel::single('wms_delivery_bill');
        $orderObj = &app::get('ome')->model('orders');
        $braObj = &app::get('ome')->model('branch');
        $opObj  = &app::get('ome')->model('operation_log');
        $omeExtOrdLib = kernel::single('ome_extint_order');
        $memberObj = &app::get('ome')->model('members');

        $dly = $dlyObj->dump($dly_id);
        $tmp = $memberObj->dump($dly['member_id']);

        $dly['member_name'] = $tmp['account']['uname'];
        $dly['members'] = "手机：".$tmp['contact']['phone']['mobile']."<br>";
        $dly['members'] .= "电话：".$tmp['contact']['phone']['telephone']."<br>";
        empty($dly['branch_id'])?$branch_id=0:$branch_id=$dly['branch_id'];
        //$shop = $dlyObj->getShopInfo($dly['shop_id']);
        //$dly['area'] = $shop['area'];

        //获取主物流单号
        $logi_no = $dlyBillLib->getPrimaryLogiNoById($dly_id);
        $dly['logi_no'] = $logi_no;

        //发货单日志
        $logdata = $opObj->read_log(array('obj_id'=>$dly_id,'obj_type'=>'delivery@wms'), 0, -1);
		foreach($logdata as $k=>$v){
			$logdata[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
		}
        $render->pagedata['log'] = $logdata;

        //发货单关联订单号
        $order_bns = $omeExtOrdLib->getOrderBns($dly['outer_delivery_bn']);
        $render->pagedata['order_bn'] = $order_bns;

        //买家备注、商家备注要走接口查询?还是wms冗余
        $res = $omeExtOrdLib->getMemoByDlyId($dly['outer_delivery_bn']);
        $render->pagedata['custom_mark'] = $res['custom_mark'];#会员备注与订单备注信息
        $render->pagedata['mark_text'] = $res['mark_text'];#会员备注与订单备注信息

        $render->pagedata['write']    = $this->write;
        $render->pagedata['write_memo']    = $this->write_memo;
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $dlyCorp = $dlyCorpObj->dump($dly['logi_id'], 'prt_tmpl_id,type,tmpl_type');
        //物流公司使用电子面单时物流单号不能被编辑
        if ($dlyCorp['tmpl_type'] == 'electron') {
            $render->pagedata['write'] = 1;
        }
        if ($dlyCorp['tmpl_type'] == 'electron' && $dly['status']!='succ') {
            $render->pagedata['write_memo'] = 1;
        }
        $render->pagedata['url']    = $this->url;

        $render->pagedata['dly_corp'] = $braObj->get_corp($branch_id,$dly['consignee']['area']);
		$dly['create_time'] = date('Y-m-d H:i:s',$dly['create_time']);

        //根据原始发货单获取配送方式
        $shipping_type = kernel::single('ome_interface_delivery')->getOmeDlyShipType($dly['outer_delivery_bn']);
        $dly['delivery'] = $shipping_type;

        $render->pagedata['dly']      = $dly;

        $render->pagedata['status'] = $_GET['status'];

        return $render->fetch('admin/delivery/delivery_detail.html');
    }

    function detail_item($dly_id){
        $render = app::get('wms')->render();
        $dlyObj = &app::get('wms')->model('delivery');

        $items = $dlyObj->getItemsByDeliveryId($dly_id);
        $delivery = $dlyObj->dump($dly_id);

        $render->pagedata['write'] = $this->write;
        $render->pagedata['items'] = $items;
        $render->pagedata['dly']   = $delivery;

        return $render->fetch('admin/delivery/delivery_item.html');
    }

    function detail_delivery($dly_id){
        $dlyObj = &app::get('wms')->model('delivery');
        $dlyChildObj = &app::get('wms')->model('delivery_bill');
        $opObj = &app::get('ome')->model('operation_log');
        $dlyCheckLib = kernel::single('wms_delivery_check');
        if(!empty($_POST)){
            $billarr =  $_POST["dlylist"];
            foreach($billarr as $k=>$v){
                $v = trim($v);
                if ($dlyCheckLib->existExpressNoBill($v, $_POST['delivery_id'],$k)){
                    echo '<script>alert("已有此物流单号:'.$v.'")</script>'; break;
                }else{
                    $dlybillinfoget = $dlyChildObj->dump(array('b_id'=>$k));
                    if(empty($dlybillinfoget['logi_no'])){
                        $logstr = '录入快递单号:'.$v;
                        $opObj->write_log('delivery_bill_add@wms', $dly_id, $logstr);
                    }else{
                        $logstr = '修改快递单号:'.$dlybillinfoget['logi_no'].'->'.$v;
                        $opObj->write_log('delivery_bill_modify@wms', $dly_id, $logstr);
                    }
                    $dlyChildObj->update(array('logi_no'=>$v),array('b_id' => $k));
                }
            }
        }

        $render = app::get('wms')->render();
        $braObj = &app::get('ome')->model('branch');

        $dly = $dlyObj->dump($dly_id);
        empty($dly['branch_id'])?$branch_id=0:$branch_id=$dly['branch_id'];
        $dlyChildList = $dlyChildObj->getList('*',array('delivery_id'=>$dly_id),0,-1);

        $render->pagedata['dlyChildListCount'] = count($dlyChildList);
        $render->pagedata['dlyChildList'] = $dlyChildList;
        $render->pagedata['dly_corp'] = $braObj->get_corp($branch_id,$dly['consignee']['area']);
        $render->pagedata['dly']   = $dly;
        $render->pagedata['write'] = $this->write;

        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $dlyCorp = $dlyCorpObj->dump($dly['logi_id'], 'prt_tmpl_id,type,tmpl_type');
        //物流公司使用电子面单时物流单号不能被编辑
        if ($dlyCorp['tmpl_type'] == 'electron') {
            $render->pagedata['write'] = 1;
        }
        $render->pagedata['dlyCorp'] = $dlyCorp;
        return $render->fetch('admin/delivery/delivery_list.html');
    }

}
?>