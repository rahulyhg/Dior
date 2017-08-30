<?php

/**
 * 快速导入模板
 *
 * @author shiyao744@sohu.com
 * @version 0.1b
 */
class ome_groupon_plugin_speed extends ome_groupon_plugin_abstract implements ome_groupon_plugin_interface {
	
	public $_name = '快速导入模板';
	

	/**
	 * 处理导入到原始数据
	 *
	 * @param array $data 原始数据
	 * @return Array
	 */
	public function process($data, $post) {
		
		return parent::process($data, $post);
	}
	
	public function convertToRowSdf($row, $post) {
		$row_sdf = array ();
		
		$order_bn = '';
		if ($row [0]) {
			$order_bn = str_replace ( '`', '', $row [0] );
		}
		
		$consignee_name = '';
		if ($row [1]) {
			$consignee_name = $row [1];
		}
		
		$consignee_area_province = '';
		$consignee_area_city = '';
		$consignee_area_county = '';
		$consignee_area_addr = '';
		if ($row [2]) {
			$consignee_area_province = $row [2];
		}
		
		if ($row [3]) {
			$consignee_area_city = $row [3];
		}
		
		if ($row [4]) {
			$consignee_area_county = $row [4];
		}
		
		if ($row [5]) {
			$consignee_area_addr = $row [5];
		}
		
		$consignee_mobile = '';
		if ($row [6]) {
			$consignee_mobile = $row [6];
		}
		
		$consignee_tel = '';
		if ($row [7]) {
			$consignee_tel = $row [7];
		}
		
		$product_nums = '';
		if ($row [8]) {
			$product_nums = $row [8];
		}
		
		$shipping_name = '';
		if ($row [9]) {
			$shipping_name = $row [9];
		}
		
		$custom_mark = '';
		if ($row [10]) {
			$custom_mark = $row [10];
		}
		
		$createtime = '';
		if ($row [11]) {
			$createtime = strtotime($row [11]);
		}
		
		$product_bn = '';
		if ($row [12]) {
			$product_bn = $row [12];
		}
		
		$product_price = '';
		if ($row [13]) {
			$product_price = $row [13];
		}

		$cost_freight = 0;
		if($row[14]){
			$cost_freight = $row[14];
		}
		
		$mark_text = '';
		if ($row [15]) {
		    $mark_text = $row [15];
		}
		
		
		

		$shipping_cod = false;
		$is_tax = false;
		
		
		$cost_item = $product_price * $product_nums;
		$total_amount = $cost_item + $cost_freight;
		
		if ($row [16]) {
		    $row[16] = trim($row[16]);
		    #货到付款
		    if( ($row[16] == '是') || ($row[16] == 'true')||($row[16] == 'TRUE') ||($row[16] == 'yes') ||($row[16] == 'YES')){
		        $shipping_cod = 'true';
		    }
		}
		$row_sdf = array(
    		'order_bn'=>trim($order_bn),
	    	'shipping'=>array(
	    		'shipping_name'=>$shipping_name,
    			'is_cod'=>$shipping_cod,
    			'cost_shipping'=>$cost_freight,    			
    	    ),
	    	'custom_mark'=>$custom_mark,
		    'mark_text'=>$mark_text,
	    	'consignee'=>array(
        'name'=>$consignee_name,
        'email'=>$consignee_email,
        'zip'=>$consignee_zip,
        'mobile'=>$consignee_mobile,
        'telephone' => $consignee_tel,
        'addr'=>$consignee_area_addr,
        'area'=>
        array(
         'province'=>$consignee_area_province,
         'city'=>$consignee_area_city,
         'county'=>$consignee_area_county,
        ),
    		),
	    	'is_tax'=>$is_tax,
	    	'cost_item'=>$cost_item,
	    	'total_amount'=>$total_amount,
	    	'product_bn'=>$product_bn,
	    	'product_price'=>$product_price,
	    	'product_nums'=>$product_nums,
    	);
		return $row_sdf;
	}

}