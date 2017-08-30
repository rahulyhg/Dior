<?php
class ome_mdl_order_groupon extends dbeav_model {
	
	var $has_many = array ('order_groupon_items' => 'order_groupon_items' );
	
	function io_title($filter, $ioType = 'csv') {
		switch ($filter) {
			case 'export' :
				$this->oSchema ['csv'] [$filter] = array ('*:订单号' => 'order_bn', '*:物流单号' => 'logi_no' , '*:发货状态' => 'ship_status');
				break;
		
		}
		$this->ioTitle [$ioType] [$filter] = array_keys ( $this->oSchema [$ioType] [$filter] );
		return $this->ioTitle [$ioType] [$filter];
	}
	
	//csv导出
	function fgetlist_csv(&$data, $filter, & $offset, $exportType = 1) {
		$title = array ();
		foreach ( $this->io_title ( 'export' ) as $k => $v ) {
			$title [] = $v;
		}
		$data ['title'] = '"' . implode ( '","', $title ) . '"';
		
		$orders = $this->getGrouponOrders ( $_GET ['order_groupon_id'] );
		$data ['contents'] = array ();
		$hash_ship_status = array (0 => '未发货', 1 => '已发货', 2 => '部分发货', 3 => '部分退货', 4 => '已退货' );
		foreach ( $orders as $order ) {
		    $order ['order_bn'] = "=\"\"".$order ['order_bn']."\"\"";
		    $order ['logi_no'] = "=\"\"".$order ['logi_no']."\"\"";
			$data ['contents'] [] = '"' . $order ['order_bn'] . '","' . $order ['logi_no'] . '","' . $hash_ship_status[$order ['ship_status']] . '"';
		}
		
		$offset = 1;
		
		return false;
	}
	
	function getGrouponOrders($order_groupon_id) {
		$list = $this->db->select ( 'select order_id from sdb_ome_order_groupon_items where order_groupon_id=' . $order_groupon_id );
		$order_ids = array ();
		foreach ( $list as $row ) {
			$order_ids [] = $row ['order_id'];
		}
		
		$list = $this->db->select ( 'select order_bn,logi_no,ship_status from sdb_ome_orders where order_id in(' . implode ( ',', $order_ids ) . ') order by ship_status' );
		
		return $list;
	}

}
?>