<?php
class ome_finder_bill{
    var $detail_basic = "支付单详情";
    
    function detail_basic($payment_id){
        $render = app::get('ome')->render();
        $oPayment = &app::get('ome')->model('payments');
        $oOrder = &app::get('ome')->model('orders');
        if ($_POST)
        {
            $data['order_id'] = $_POST['order_id'];
            $data['tax_no'] = $_POST['tax_no'];
            $oOrder->save($data);

            //TODO:api，发票号的回写
            $oOperation_log = &app::get('ome')->model('operation_log');
            $oOperation_log->write_log('order_modify@ome',$_POST['order_id'],'录入及变更发票号');
        }
        $pay_detail = $oPayment->dump($payment_id);
        $orderinfo = $oOrder->order_detail($pay_detail['order_id']);
        
        //如果是前端支付单,操作员则显示前端店铺名称
        if (empty($pay_detail['op_id'])){
            if ($pay_detail['shop_id']){
               $oShop = &app::get('ome')->model('shop');
               $shop_detail = $oShop->dump($pay_detail['shop_id'], 'node_type');
               $pay_detail['op_id'] = $shop_detail['node_type'];
            }
        }else{
            $user = app::get('desktop')->model('users')->dump($pay_detail['op_id'],'*',array( ':account@pam'=>array('*') ));
            $pay_detail['op_id'] = $user['name'] ? $user['name'] : '-';
        }
        
        $render->pagedata['detail'] = $pay_detail;
        $render->pagedata['orderinfo'] = $orderinfo;
        return $render->fetch('admin/payment/detail.html');
    }
    var $addon_cols = 'archive,order_id';
    var $column_order_id='订单号';
    var $column_order_id_width='100';
    function column_order_id($row)
    {
        $archive = $row[$this->col_prefix . 'archive'];
        
        $order_id = $row[$this->col_prefix . 'order_id'];
        
        if ($archive == '1' ) {
            $orderObj = app::get('archive')->model('orders');
            
        }else{
            $orderObj = app::get('ome')->model('orders');
            
        }
        $filter = array('order_id'=>$order_id);
        $order = $orderObj->dump($filter,'order_bn');

        return $order['order_bn'];
    }

	var $column_operate='操作';
    var $column_operate_width='100';
    function column_operate($row)
    {	
		$find_id = $_GET['_finder']['finder_id'];
		$paymentObj = app::get('ome')->model('payments');
		$payInfo = $paymentObj->dump($row['payment_id'],'*');
		$payment_id = $row['payment_id'];
		if($payInfo['balance_status']=='require'){


			$button_confirm = <<<EOF
			<a href="index.php?app=ome&ctl=admin_balance_pay&act=confirm_status&finder_id=$find_id&p[0]=$payment_id"   target="dialog::{width:400,height:250,title:'对账确认'}">确认</a>
EOF;
			return $button_confirm;
		}
    }
}
?>