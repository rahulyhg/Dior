<?php
/**
 * 库存同步到Magento
 * @author lijun
 * @package omeftp_service_order
 *
 */
class omemagento_service_product{
	
	
	 public function __construct(&$app){
        $this->app = $app;
		$this->request = kernel::single('omemagento_service_request');
    }

	public function update_store($sku,$num){
		$params = array('sku'=>$sku,'number'=>$num);
		$this->request->do_request('stock',$params);
	}

	public function update_price($sku,$price){
		$params = array('sku'=>$sku,'price'=>$price);
		$this->request->do_request('price',$params);
	}

}