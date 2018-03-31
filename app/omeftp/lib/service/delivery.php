<?php
/**
 * 发货同步到AX
 * @author lijun
 * @package omeftp_service_delivery
 *
 */
class omeftp_service_delivery{
    public function __construct(&$app)
    {
        $this->app = $app;

        $this->file_obj = kernel::single('omeftp_type_txt');
		$this->ftp_operate = kernel::single('omeftp_ftp_operate');

		$this->operate_log = kernel::single('omeftp_log');
		$this->math = kernel::single('eccommon_math');
    }

    /**
     * 审核订单   将订单信息写入文件 如果是手动审核 则传到FTP
     * @access public
     * @param int $delivery_id 发货单ID
     */
    public function delivery($delivery_id,$sync=false){
        $deliveryModel = app::get('ome')->model('delivery');
        $delivery = $deliveryModel->dump($delivery_id);
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');

		$delivery = $this->format_delivery($delivery);
		$file_brand = $ax_setting['ax_file_brand'];
		$file_prefix = $ax_setting['ax_file_prefix'];
		$file_arr = array($file_prefix,$file_brand,'ORDER',date('YmdHis',time()));
		$file_name = implode('_',$file_arr);

		if(!file_exists(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()))){
			mkdir(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777,true);
			chmod(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777);
		}
		$file_params['file'] = ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()).'/'.$file_name.'.dat';

		while(file_exists($file_params['file'])){
			sleep(1);
			$file_arr = array($file_prefix,$file_brand,'ORDER',date('YmdHis',time()));
			$file_name = implode('_',$file_arr);

			if(!file_exists(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()))){
				mkdir(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777,true);
				chmod(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777);
			}
			$file_params['file'] = ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()).'/'.$file_name.'.dat';
		}

		/*if($sync){
			if(AX_DIR){
				$file_params['file'] = AX_DIR.'/'.$file_prefix.date('YmdHis',time()).'.csv';
			}else{
				$file_params['file'] = PUBLIC_DIR.'/ax/'.$file_prefix.date('YmdHis',time()).'.csv';
			}
		}else{
			if(AX_DIR){
				$file_params['file'] = AX_DIR.'/'.'CN_DI_'.date('Ymd',time()).'.csv';
			}else{
				$file_params['file'] = PUBLIC_DIR.'/ax/'.'CN_DI_'.date('Ymd',time()).'.csv';
			}
		}*/
	
		$file_params['method'] = 'a';
		$file_params['data'] = $this->getContent($delivery,$file_params['file']);

		$file_log_data = array(
				'content'=>$file_params['data']?$file_params['data']:'没有数据',
				'io_type'=>'in',
				'work_type'=>'delivery',
				'createtime'=>time(),
				'status'=>'prepare',
				'file_route'=>$file_params['file'],
			);
		$file_log_id = $this->operate_log->write_log($file_log_data,'file');

		$flag = $this->file_obj->toWrite($file_params,$msg);
        while(!$flag){
            sleep(1);
            $file_arr = array($file_prefix,$file_brand,'ORDER',date('YmdHis',time()));
			$file_name = implode('_',$file_arr);

			if(!file_exists(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()))){
				mkdir(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777,true);
				chmod(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777);
			}
			$file_params['file'] = ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()).'/'.$file_name.'.dat';
            $update_file_log_data = array(
                'file_route'=>$file_params['file'],
            );
            $this->operate_log->update_log($update_file_log_data,$file_log_id,'file');
            $flag = $this->file_obj->toWrite($file_params,$msg);
        }
        
		
		if($flag){
			$this->operate_log->update_log(array('status'=>'succ','lastmodify'=>time()),$file_log_id,'file');
            $params['remote'] = $this->file_obj->getFileName($file_params['file']);
            $params['local'] = $file_params['file'];
            $params['resume'] = 0;
            $ftp_log_data = array(
                    'io_type'=>'out',
                    'work_type'=>'delivery',
                    'createtime'=>time(),
                    'status'=>'prepare',
                    'file_local_route'=>$file_params['file'],
                    'file_ftp_route'=>$params['remote'],
                );
            $ftp_log_id = $this->operate_log->write_log($ftp_log_data,'ftp');

            kernel::single('omemagento_service_order')->update_status($delivery['order']['order_bn'],'sent_to_ax');
		}
    }

	public function getContent($delivery,$file){
		//error_log(var_export($delivery,true),3,'f:/order.txt');
		$ax_content_arr = array();
		if(file_exists($file)){

		}else{
			$ax_header = app::get('omeftp')->getConf('AX_Header');
			$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
			$file_brand = $ax_setting['ax_file_brand'];
			$str = 'ORDER_REG_DIOR';
			$ax_content_arr[] = $ax_header.$str;
		}
		
		$ax_h = $this->get_ax_h($delivery);
		if(file_exists($file)){
			$ax_h = "\n".$ax_h;
		}
		$ax_content_arr [] = $ax_h;

		$ax_d = $this->get_ax_d($delivery);
		$ax_content_arr [] = $ax_d;

		$ax_i = $this->get_ax_i($delivery);
		$ax_content_arr [] = $ax_i;  //发票功能暂时无法支持

		$az_l = $this->get_ax_l($delivery);
		$ax_content_arr [] = $az_l;
		
		$content = implode("\n",$ax_content_arr);
		return $content;
	}

	public function get_ax_h($delivery){
		$ax_h = array();
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');

		$ax_h_h = $ax_setting['ax_h'];
		$ax_h[] = $ax_h_h?$ax_h_h:'H';
		
		$ax_h_sales_country_code = $ax_setting['ax_h_sales_country_code'];
		$ax_h[] = $ax_h_sales_country_code?$ax_h_sales_country_code:'CN';
		
		$ax_h_salas_division = $ax_setting['ax_h_salas_division'];
		$ax_h[] = $ax_h_salas_division?$ax_h_salas_division:'01';
		
		$ax_h_sales_organization = $ax_setting['ax_h_sales_organization'];
		$ax_h[] = $ax_h_sales_organization?$ax_h_sales_organization:'2920';

		$ax_h_plant = $ax_setting['ax_h_plant'];
		$ax_h[] = $ax_h_plant?$ax_h_plant:'1190';

		$ax_h[] = $delivery['order']['order_bn'];//字段意思不明确，待定

		$ax_h[] = '';//AX SO number
		
		$ax_h_customer_account = $ax_setting['ax_h_customer_account'];
		$ax_h[] = $ax_h_customer_account?$ax_h_customer_account:'C4010P1';// 固定参数  值待定
		
		$ax_h_invoice_ccount = $ax_setting['ax_h_invoice_ccount'];
		$ax_h[] = $ax_h_invoice_ccount?$ax_h_invoice_ccount:'C4010P1';//固定参数  值待定
		
		$ax_h_sales_order_status = $ax_setting['ax_h_sales_order_status'];
		$ax_h[] = $ax_h_sales_order_status?$ax_h_sales_order_status:'SEND_TO_ERP';// Sales order Status
		
		$custom_mark = unserialize($delivery['order']['custom_mark']);
		$custom_memo = $custom_mark[0]['op_content']; 
		
		$golden_box='';
		if($delivery['order']['golden_box']=='true'){
			$golden_box=' 圣诞金色包装';
		}
		
		if($custom_memo){
			$ax_h[] = $custom_memo.','.$golden_box; //Sales Description
		}else{
			$ax_h[] = $golden_box;
		}

		
		$ax_h_currency = $ax_setting['ax_h_currency'];
		$ax_h[] = $ax_h_currency?$ax_h_currency:'CNY';// currency

		if($delivery['order']['shipping']['cost_shipping']>$delivery['order']['pmt_cost_shipping']){
			$cost_shipping = $this->math->number_plus(array(($delivery['order']['shipping']['cost_shipping']-$delivery['order']['pmt_cost_shipping']),0));
			$delivery['order']['pmt_cost_shipping'] = '0.00';
		}else{
			$cost_shipping = '0.00';
			$delivery['order']['pmt_cost_shipping'] = $delivery['order']['pmt_cost_shipping']-$delivery['order']['shipping']['cost_shipping'];
		}
		$ax_h[] = $cost_shipping;// freight amount 配送费用

		$ax_h[] = ($delivery['is_cod']=='true')?$this->math->number_plus(array($delivery['order']['payinfo']['cost_payment']-$delivery['order']['pmt_cost_shipping'],0)):'0.00';//cod fee amount

		$ax_h[] = '0.00'; //total discount amount  优惠金额

		$ax_h[] = '';//total  discount %

		$itemNums = $delivery['itemNum'];
		if($delivery['order']['message1']||$delivery['order']['message2']||$delivery['order']['message3']||$delivery['order']['message4']||$delivery['order']['message5']||$delivery['order']['message6']){
			$itemNums += 1;
		}
		if($delivery['order']['is_w_card']=='true'){
			$itemNums +=1 ;
		}
		if(!empty($delivery['order']['ribbon_sku'])){
			$itemNums +=1 ;
		}

		$ax_h[] = intval($itemNums);//total quantity

		$ax_h[] = '';//Alt. delivery account
		$ax_h[] = date('Y-m-d H:i:s',$delivery['order']['createtime']);//date of order creation 订单创建时间

		$ax_h[] = '';//Language

		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = '';

		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = '';
		//兑换订单需找到原始订单号
		if($delivery['order']['shop_type']=="minishop"){
			$arrCards=array();
			$objCards=app::get("giftcard")->model('cards');
			$arrCards=$objCards->getList("p_order_bn",array('order_bn'=>$delivery['order']['order_bn']),0,1);
			$ax_h[] = $arrCards[0]['p_order_bn'];
		}else{
			$ax_h[] = $delivery['order']['order_bn'];
		}

		
		return implode('|',$ax_h);
	}

	public function get_ax_d($delivery){
		$ax_d = array();
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');

		$ax_d[] = 'D';

		$ax_d[] = '';//Requested receipt Date
		$ax_d[] = '';//Requested Ship Date
		$ax_d[] = '';//Confirmed receipt Date
		$ax_d[] = '';//Confirmed Ship Date

		$ax_d[] = '';//配送时间  暂时留空

		$ax_d[] = '';//Condition of Delivery   set by AX
		
		$ax_d_mode_of_delivery = $ax_setting['ax_d_mode_of_delivery'];
		if($delivery['consignee']['province']=='上海'||$delivery['consignee']['province']=='江苏省'||$delivery['consignee']['province']=='浙江省'||$delivery['consignee']['province']=='安徽省'||$delivery['consignee']['province']=='西藏自治区'){
			$ax_d[] = 'SF_STD';//
		}else{
			$ax_d[] = 'SF_SP';
		}

		$ax_d[] = '';//Packing Slip number
		$ax_d[] = '';//Shipping Date

		$ax_d[] = '';//Shipping Tracking URL
		$ax_d[] = '';//Shipping tracking ID

		$ax_d[] = $delivery['consignee']['name'];//Delivery Name
		$ax_d[] = $delivery['consignee']['district'].'==CR=='.$delivery['consignee']['addr'];//Delivery Street name
		$ax_d[] = substr($delivery['consignee']['zip'],0,10);//Delivery ZIP 
		$ax_d[] = $delivery['consignee']['city'];//Delivery City
		$ax_d[] = $delivery['consignee']['province'];//Delivery State ID
		$ax_d[] = 'CN';//Delivery Country/Region
		$ax_d[] = $delivery['consignee']['mobile'];//Delivery Contact
		$ax_d[] = '';//Order Total Weight

		$ax_d[] = '';//3rd Party Id
		$ax_d[] = '';//3rd Party Name
		$ax_d[] = '';//3rd Party Street name
		$ax_d[] = '';//3rd Party ZIP / Postal code
		$ax_d[] = '';//3rd Party City
		$ax_d[] = '';//3rd Party State ID
		$ax_d[] = '';//3rd Party Country/Region
		$ax_d[] = '';//3rd Party

		return implode('|',$ax_d);
	}

	
	public function get_ax_i($delivery){
		$ax_i = array();
 //echo "<pre>";print_r($delivery);exit;
		// 发票地址
		$invoice_area = $delivery['order']['invoice_area'];
		kernel::single('ome_func')->split_area($invoice_area);
		$invoice_state    = ome_func::strip_bom(trim($invoice_area[0]));
		$invoice_city     = ome_func::strip_bom(trim($invoice_area[1]));
		$invoice_district = ome_func::strip_bom(trim($invoice_area[2]));

		$ax_i[] = 'I';

		$ax_i[] = $delivery['order']['invoice_name']?$delivery['order']['invoice_name']:'';//Invoice  Name//$delivery['member_id'];//Bill to customer
		$ax_i[] = '';//Payment Term
		if($delivery['order']['pay_bn']=='cod'){
			 $pay_bn = 'COD';
		}
		if($delivery['order']['pay_bn']=='alipay'){
			 $pay_bn = 'ALIPAY';
		}
		if($delivery['order']['pay_bn']=='wxpayjsapi'){
			 $pay_bn = 'WECHAT';
		}
		$ax_i[] = $pay_bn;//Method of payment   prepaid or cod
		$ax_i[] = '';//Invoice Number by ax  Ax invoice number
		$ax_i[] = '';//Invoice date 
		$ax_i[] = $this->math->number_plus(array($delivery['order']['cost_tax'],0));//Total Amount incl. taxes
		if($delivery['order']['is_tax']=='true'&&$delivery['order']['is_einvoice']=='false'){
			$ax_i[] = $delivery['order']['tax_title']?$delivery['order']['tax_title']:$delivery['consignee']['name'];//Invoice  Name

			$ax_i[] = $invoice_district?($invoice_district.'==CR=='.$delivery['order']['invoice_addr']):($delivery['consignee']['district'].'==CR=='.$delivery['consignee']['addr']);//Invoice  Street name
			$ax_i[] = $delivery['order']['invoice_zip']?$delivery['order']['invoice_zip']:$delivery['consignee']['zip'];//Invoice  ZIP / Postal code
			$ax_i[] = $invoice_city?$invoice_city:$delivery['consignee']['city'];//Invoice City
			$ax_i[] = $invoice_state;//Invoice  State ID
			$ax_i[] = 'CN';//Invoice  Country/Region
			$ax_i[] = $delivery['order']['invoice_contact']?$delivery['order']['invoice_contact']:$delivery['consignee']['mobile'];//Invoice Contact
		}else{
			$ax_i[] = '';
			$ax_i[] = '';
			$ax_i[] = '';
			$ax_i[] = '';
			$ax_i[] = '';
			$ax_i[] = '';
			$ax_i[] = '';
		}

		$ax_i[] = '';//Company legal form
		$ax_i[] = '';//Our Tax exempt number
		$ax_i[] = '';//Cust. Tax exempt number
		$ax_i[] = '';//Payment Term desc.
		$ax_i[] = '';//Payment due date
		$ax_i[] = '';//Discount date
		$ax_i[] = '';//Discount percent
		$ax_i[] = '';//Discount amount
		$ax_i[] = '';//Total Amount excl. Taxes

		$ax_i[] = '';//Total Sales taxes
		
		$ax_i[] = $delivery['order']['taxpayer_identity_number'];
		return implode('|',$ax_i);
	}

	public function get_ax_l($delivery){
		$ax_l = array();
		$orderObjModel = app::get('ome')->model('order_objects');
		foreach($delivery['delivery_items'] as $key=>$delivery_items){
			$order_obj_items = $orderObjModel->dump($delivery_items['obj_id']);

			$ax_l[$key][] = 'L';
			if($order_obj_items['obj_type']=='goods'){
				$ax_l[$key][] = 'Sales';//SAP Item Type   eg.Sales  Gift  sample
			}elseif($order_obj_items['obj_type']=='gift'){
				$ax_l[$key][] = 'Sample';//SAP Item Type   eg.Sales  Gift  sample
			}elseif($order_obj_items['obj_type']=='sample'){
				$ax_l[$key][] = 'Gift';//SAP Item Type   eg.Sales  Gift  sample
			}else{
				$ax_l[$key][] = 'Sales';//SAP Item Type   eg.Sales  Gift  sample
			}

			$ax_l[$key][] = '';//AX SO line number
			$ax_l[$key][] = $key+1;//External SO line number
			$ax_l[$key][] = $delivery_items['bn'];//Item Number

			$ax_l[$key][] = '';//Text Detailled description of the item 
			$ax_l[$key][] = $delivery_items['name'];//Item description
			$ax_l[$key][] = $delivery_items['item_id'];//External Item Code
			$ax_l[$key][] = '';//Bar code of the salable item  //条形码
			if($order_obj_items['obj_type']=='sample'){
				$ax_l[$key][] = $delivery_items['message1'];//First line of the gift message
				$ax_l[$key][] = $delivery_items['message2'];//Second line of the gift message
				$ax_l[$key][] = $delivery_items['message3'];//Third line of the gift message
				$ax_l[$key][] = $delivery_items['message4'];//Fourth line of the gift message
			}else{
				$ax_l[$key][] = '';//First line of the gift message
				$ax_l[$key][] = '';//Second line of the gift message
				$ax_l[$key][] = '';//Third line of the gift message
				$ax_l[$key][] = '';//Fourth line of the gift message
			}

			$ax_l[$key][] = intval($order_obj_items['quantity']);//Ordered quantity  Sales ordered quantity
			$ax_l[$key][] = $this->math->number_plus(array($order_obj_items['price'],0));//Sales Retail Price  Unit price on of the Sales Order Line
			$ax_l[$key][] = '';//Price unit  Price Unit of the Sales order Line

			$ax_pmt_price = $delivery_items['ax_pmt_price']/intval($order_obj_items['quantity']);
			$ax_l[$key][] = $this->math->number_plus(array($ax_pmt_price,0));//Discount amount
			$ax_l[$key][] = $this->math->number_plus(array($delivery_items['ax_pmt_percent'],0));//Discount % 
			$ax_l[$key][] = '';//Discount % Level 1
			$ax_l[$key][] = '';//Discount % Level 2
			$ax_l[$key][] = '';//Discount % Level 3
			$ax_l[$key][] = '';//Shipped Qty

			$ax_l[$key][] = '';//Invoiced Qty
			$ax_l[$key][] = '';//Picking in progress Qty
			$ax_l[$key][] = '';//Picked Qty
			$ax_l[$key][] = 'Ea';//Item Sales Unit
			$ax_l[$key][] = '';//Total Discount Amount excl. Tax
			$ax_l[$key][] = '';//Discount label
			$ax_l[$key][] = '';//Line amount excl. Taxes
			$ax_l[$key][] = '';//Item Sales Tax Group
			$ax_l[$key][] = '';//Sales Tax rate
			$ax_l[$key][] = '';//Sales Tax amount
			$ax_l[$key][] = '';//Line amount incl. Taxes

			$ax_l[$key][] = '';//Batch Number

			$ax_l_str[$key] = implode('|',$ax_l[$key]);
		}
		$key = $key+1;
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');

		if($delivery['order']['message1']||$delivery['order']['message2']||$delivery['order']['message3']||$delivery['order']['message4']||$delivery['order']['message5']||$delivery['order']['message6']){
			$ax_l_str[] = 'L|Gift||'.($key+1).'|'.$ax_setting['ax_sample_bn'].'|||||'.$delivery['order']['message1'].'==CR=='.$delivery['order']['message2'].'==CR=='.$delivery['order']['message3'].'==CR=='.$delivery['order']['message4'].'==CR=='.$delivery['order']['message5'].'==CR=='.$delivery['order']['message6'].'||||1|0.00|||||||||||Ea||||||||';
			$key = $key+1;
		}

		if($delivery['order']['is_w_card']=='true'){
			$ax_l_str[] = 'L|Card||'.($key+1).'|'.$ax_setting['ax_gift_bn'].'|||||||||1|0.00|||||||||||Ea||||||||';
			$key = $key+1;
		}
		
		if(!empty($delivery['order']['ribbon_sku'])){
			$arrRibbon=array();
			$arrRibbon=kernel::single("ome_mdl_products")->getList("name",array("bn"=>$delivery['order']['ribbon_sku']));
			$arrRibbon=$arrRibbon[0];
			
			$ax_l_str[] = 'L|Ribbon||'.($key+1).'|'.$delivery['order']['ribbon_sku'].'||'.$arrRibbon['name'].'|||||||1|0.00|||||||||||Ea||||||||';
		}
		
		return implode("\n",$ax_l_str);
	}

	
	/**
     * 获取必要的数据
     *
     * @param Array $delivery 发货单信息
     * @return MIX
     * @author
     **/
	public function format_delivery($delivery){
		$orderModel     = app::get('ome')->model('orders');
        $deliOrderModel = app::get('ome')->model('delivery_order');
        $deliveryModel      = app::get('ome')->model('delivery');

		// 判断发货单类型
        switch ($delivery['type']) {
            case 'reject':  // 售后发货单
                // 订单信息
                if ($delivery['order']['order_id']) {
                    $order_id = $delivery['order']['order_id'];
                } else {
                    $deliOrder = $deliOrderModel->dump(array('delivery_id'=>$delivery['delivery_id']),'*');
                    $order_id = $deliOrder['order_id'];
                }

                $order = $orderModel->dump(array('order_id'=>$order_id),'order_bn,cost_payment,shop_id,invoice_name,createtime,cost_tax,invoice_area,invoice_addr,invoice_zip,invoice_contact,is_tax,is_delivery,is_w_card,mark_text,sync,welcomecard,tax_company,pmt_order,custom_mark,ship_area,order_id,self_delivery,createway');

                // 发货人地址
                $consignee_area = $delivery['consignee']['area'];
                kernel::single('ome_func')->split_area($consignee_area);
                $receiver_state    = ome_func::strip_bom(trim($consignee_area[0]));
                $receiver_city     = ome_func::strip_bom(trim($consignee_area[1]));
                $receiver_district = ome_func::strip_bom(trim($consignee_area[2]));

                $delivery['receiver']['receiver_state']    = $receiver_state;
                $delivery['receiver']['receiver_city']     = $receiver_city;
                $delivery['receiver']['receiver_district'] = $receiver_district;

                $delivery['logi_no'] = $order['order_bn'];
                $delivery['logi_name'] = '其他物流公司';

                $delivery['dly_corp'] = array(
                    'type' => 'OTHER',
                    'name' => '其他物流公司',
                );

                break;
            case 'normal':  // 普通发货单
                // 如果是合并发货单，取父发货单物流信息
                $parent_id = $delivery['parent_id'];
                if ($parent_id > 0) {
                    $pDelivery = $deliveryModel->dump(array('delivery_id'=>$parent_id),'*');
                    $delivery['status']    = $pDelivery['status'];
                    $delivery['logi_id']   = $pDelivery['logi_id'];
                    $delivery['logi_name'] = $pDelivery['logi_name'];
                    $delivery['logi_no']   = $pDelivery['logi_no'];
                    $delivery['logi_code'] = $pDelivery['logi_code'];
                }

                // 物流发货单去BOM头
                $pattrn              = chr(239).chr(187).chr(191);
                $delivery['logi_no'] = trim(str_replace($pattrn, '', $delivery['logi_no']));

                // 如果订单信息不存在，重新读取
                if (!$delivery['order']) {
                    $deliOrder = $deliOrderModel->dump(array('delivery_id'=>$delivery['delivery_id']),'*');

                    $delivery['order'] = $orderModel->dump(array('order_id'=>$deliOrder['order_id']),'order_bn,cost_payment,shop_id,shop_type,welcomecard,pmt_order,createtime,invoice_name,cost_tax,invoice_area,invoice_addr,invoice_zip,invoice_contact,is_tax,tax_company,cost_freight,is_delivery,mark_text,custom_mark,sync,ship_area,order_id,self_delivery,createway,pmt_cost_shipping,is_w_card,pay_bn,message1,message2,message3,message4,message5,message6,discount,total_amount,taxpayer_identity_number,golden_box,ribbon_sku,is_einvoice');
                }

                // 发货地址
                $consignee_area = $delivery['consignee']['area'];
                kernel::single('ome_func')->split_area($consignee_area);
                $receiver_state    = ome_func::strip_bom(trim($consignee_area[0]));
                $receiver_city     = ome_func::strip_bom(trim($consignee_area[1]));
                $receiver_district = ome_func::strip_bom(trim($consignee_area[2]));

                $delivery['receiver']['receiver_state']    = $receiver_state;
                $delivery['receiver']['receiver_city']     = $receiver_city;
                $delivery['receiver']['receiver_district'] = $receiver_district;

                // 物流公司信息
                $dlyCorpModel = app::get('ome')->model('dly_corp');
                $delivery['dly_corp'] = $dlyCorpModel->dump(array('corp_id'=>$delivery['logi_id']),'type,name');

                break;
            default:
                return false;

                break;
        }

		$orderItemModel     = app::get('ome')->model('order_items');
		$develiy_items      = $orderItemModel->getList('name, bn, nums as number,price,ax_pmt_price,ax_pmt_percent,pmt_price,amount,obj_id', array('order_id'=>$delivery['order']['order_id'], 'delete'=>'false'));

		// 过滤发货单明细中的空格
		foreach((array)$develiy_items as $key=>$item){
			$delivery_items[$key] = array_map('trim', $item);
		}

        $delivery['delivery_items'] = $develiy_items;

		// 会员信息
        $memberModel = app::get('ome')->model('members');
        $delivery['member'] = $memberModel->dump(array('member_id'=>$delivery['member_id']),'uname,name');

        return $delivery;
	}


}