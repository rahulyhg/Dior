<?php
class sales_aftersale_type_refuse{

	function generate_aftersale($reship_id = null){
         
        if(empty($reship_id)) return false;

		$Omembers = &app::get('ome')->model('members');
		$Oorder = &app::get('ome')->model('orders');
		$Oaftersale = &app::get('sales')->model('aftersale');
		$Oshop = &app::get('ome')->model('shop');
		$Oreship = &app::get('ome')->model('reship');
		$Opam = &app::get('pam')->model('account');

		$reshipData = $Oreship->getList('*',array('reship_id'=>$reship_id),0,1);
        
		$shopData = $Oshop->getList('name,shop_bn',array('shop_id'=>$reshipData[0]['shop_id']),0,1);
		$orderData = $Oorder->getList('member_id,order_bn',array('order_id'=>$reshipData[0]['order_id']),0,1);
		$shopData = $Oshop->getList('name,shop_bn',array('shop_id'=>$reshipData[0]['shop_id']),0,1);
		$memberData = $Omembers->getList('uname',array('member_id'=>$orderData[0]['member_id']),0,1);
		$pamData = $Opam->getList('login_name',array('account_id'=>$reshipData[0]['op_id']),0,1);
        //
        $is_archive = kernel::single('archive_order')->is_archive($reshipData[0]['source']);
        if ($is_archive) {
            $Oorder = &app::get('archive')->model('orders');
            $orderData = $Oorder->getList('member_id,order_bn',array('order_id'=>$reshipData[0]['order_id'],'flag'=>'1'),0,1);
        }

		$data['shop_id'] = $reshipData[0]['shop_id'];
		$data['shop_bn'] = $shopData[0]['shop_bn'];
		$data['shop_name'] = $shopData[0]['name'];
		$data['order_id'] = $reshipData[0]['order_id']; 
		$data['order_bn'] = $orderData[0]['order_bn']; # order_bn
		$data['reship_id'] = $reshipData[0]['reship_id'];
		$data['reship_bn'] = $reshipData[0]['reship_bn'];# reship_bn
		$data['return_type'] = 'refuse';
		$data['member_uname'] = $memberData[0]['uname'];  #uname
		$data['ship_mobile'] = $reshipData[0]['ship_mobile'];
		$data['check_op_id'] = $reshipData[0]['op_id'];
		$data['check_op_name'] = $pamData[0]['login_name'];
		$data['check_time'] = $reshipData[0]['t_end'];
		$data['aftersale_time'] = time();

		$Oreship_items = &app::get('ome')->model('reship_items');
        $Obranch = &app::get('ome')->model('branch');
		$reshipitemData = $Oreship_items->getList('*',array('reship_id'=>$reship_id));
        $branch_datas = $Obranch->getList('name,branch_id');
		
		foreach($branch_datas as $v){
		    $branch_data[$v['branch_id']] = $v['name'];
		}

        unset($branch_datas);
		if ($is_archive) {
            $data['archive'] = '1';
        }
		foreach($reshipitemData as $k=>$v){
			$data['aftersale_items'][$k]['bn'] = $v['bn'];
			$data['aftersale_items'][$k]['product_name'] = $v['product_name'];
			$data['aftersale_items'][$k]['num'] = $v['num'];
			$data['aftersale_items'][$k]['price'] = $v['price'];
			$data['aftersale_items'][$k]['branch_name'] = $branch_data[$v['branch_id']];
			$data['aftersale_items'][$k]['branch_id'] = $v['branch_id'];
			$data['aftersale_items'][$k]['product_id'] = $v['product_id'];
			$data['aftersale_items'][$k]['saleprice'] = $v['price']*$v['num'];
			$data['aftersale_items'][$k]['return_type'] = $v['return_type'];
		}

		return $data;
	}

}