<?php
class ome_finder_statement{
    var $detail_basic = "对账单详情";

	var $detail_remark = "对账单备注";
    
    function detail_basic($statement_id){
        $render = app::get('ome')->render();
        $oPayment = &app::get('ome')->model('statement');
        $oOrder = &app::get('ome')->model('orders');
		
        $detail = $oPayment->dump($statement_id);
        $orderinfo = $oOrder->order_detail($detail['order_id']);
        
        
        $render->pagedata['detail'] = $detail;
        $render->pagedata['orderinfo'] = $orderinfo;
        return $render->fetch('admin/balance/detail.html');
    }
	
	function detail_remark($statement_id){
        $render = app::get('ome')->render();
        $oPayment = &app::get('ome')->model('statement');
		if($_POST){
			$post_data = $_POST;
			$data = array('statement_id'=>$statement_id,'remark'=>$post_data['remark']);
			$oPayment->save($data);
		}
        $detail = $oPayment->dump($statement_id);
        
        $render->pagedata['detail'] = $detail;
        return $render->fetch('admin/balance/detail_remark.html');
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


	var $column_order_time='下单时间';
    var $column_order_time_width='130';
    function column_order_time($row)
    {
        $archive = $row[$this->col_prefix . 'archive'];
        
        $order_id = $row[$this->col_prefix . 'order_id'];
        
        if ($archive == '1' ) {
            $orderObj = app::get('archive')->model('orders');
            
        }else{
            $orderObj = app::get('ome')->model('orders');
            
        }
        $filter = array('order_id'=>$order_id);
        $order = $orderObj->dump($filter,'createtime');

        return date('Y-m-d H:i:s',$order['createtime']);
    }

	var $column_operate='操作';
    var $column_operate_width='100';
    function column_operate($row)
    {	
		$find_id = $_GET['_finder']['finder_id'];
		$paymentObj = app::get('ome')->model('statement');
		$payInfo = $paymentObj->dump($row['statement_id'],'*');
		$payment_id = $row['statement_id'];
		if($payInfo['balance_status']=='require'){


			$button_confirm = <<<EOF
			<a href="index.php?app=ome&ctl=admin_statement&act=confirm_status&finder_id=$find_id&p[0]=$payment_id"   target="dialog::{width:400,height:250,title:'对账确认'}">确认</a>
EOF;
			return $button_confirm;
		}else if($payInfo['balance_status']=='not_has'){
			$button_confirm = <<<EOF
			<a href="index.php?app=ome&ctl=admin_statement&act=confirm_cancel&finder_id=$find_id&p[0]=$payment_id"   target="dialog::{width:400,height:250,title:'对账确认'}">作废</a>
EOF;
			
			return $button_confirm;
		}
    }
}
?>	