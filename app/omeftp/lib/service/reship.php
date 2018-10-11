<?php
/**
 * 退货货同步到AX
 * @author lijun
 * @package omeftp_service_delivery
 *
 */
class omeftp_service_reship{
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
    public function delivery($delivery_id,$reship_id,$return_type='return',$change=array()){
        $deliveryModel = app::get('ome')->model('delivery');
        $delivery = $deliveryModel->dump($delivery_id);
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');

		$delivery = $this->format_delivery($delivery);
		$file_brand = $ax_setting['ax_file_brand'];
		$file_prefix = $ax_setting['ax_file_prefix'];

		$file_arr = array($file_prefix,$file_brand,'RETURN',date('YmdHis',time()));
		$file_name = implode('_',$file_arr);
		

		if(!file_exists(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()))){
			mkdir(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777,true);
			chmod(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777);
		}
		$file_params['file'] = ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()).'/'.$file_name.'.dat';
        
        while(file_exists($file_params['file'])){
			sleep(1);
			$file_arr = array($file_prefix,$file_brand,'RETURN',date('YmdHis',time()));
			$file_name = implode('_',$file_arr);

			if(!file_exists(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()))){
				mkdir(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777,true);
				chmod(ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()),0777);
			}
			$file_params['file'] = ROOT_DIR.'/ftp/Testing/in/'.date('Ymd',time()).'/'.$file_name.'.dat';
		}
        
		$file_params['method'] = 'a';
		$file_params['data'] = $this->getContent($delivery,$file_params['file'],$reship_id);
		
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
		if($flag){
			$this->operate_log->update_log(array('status'=>'succ','lastmodify'=>time()),$file_log_id,'file');
			$params['remote'] = $this->file_obj->getFileName($file_params['file']);
			$params['local'] = $file_params['file'];
			$params['resume'] = 0;

			$ftp_log_data = array(
					'io_type'=>'out',
					'work_type'=>'reship',
					'createtime'=>time(),
					'status'=>'prepare',
					'file_local_route'=>$file_params['file'],
					'file_ftp_route'=>$params['remote'],
				);
			$ftp_log_id = $this->operate_log->write_log($ftp_log_data,'ftp');

		}else{
			$this->operate_log->update_log(array('status'=>'fail','memo'=>$msg),$file_log_id,'file');
			//发送报警邮件
			$reshipData = app::get('ome')->model('reship')->getList('reship_bn',array('reship_id'=>$reship_id));
			$reship_bn  = $reshipData[0]['reship_bn'];
			$ax_content = $file_log_data['content'];
			$file_route = $file_log_data['file_route'];

			$acceptor = app::get('desktop')->getConf('email.config.wmsapi_acceptoremail');
			$subject = '【Dior-PROD】ByPass退单#'.$reship_bn.'退货SO文件生成失败';//【ADP-PROD】ByPass订单#10008688发送失败
			$bodys = "<font face='微软雅黑' size=2>Hi All, <br/>下面是SO文件内容和错误信息。<br>SO文件内容：<br>$ax_content<br/><br>SO文件全路径：<br>$file_route<br/><br>错误信息是：<br>$msg<br/><br/>本邮件为自动发送，请勿回复，谢谢。<br/><br/>D1M OMS 开发团队<br/>".date("Y-m-d H:i:s")."</font>";
			kernel::single('emailsetting_send')->send($acceptor,$subject,$bodys);
		}
    }
	public function getContent($delivery,$file,$reship_id){
		$ax_content_arr = array();

		$ax_header = app::get('omeftp')->getConf('AX_Header');
		$ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
		$file_brand = $ax_setting['ax_file_brand'];
		$str = 'ORDER_RET_DIOR';
		$ax_content_arr[] = $ax_header.$str;
		
		$ax_h = $this->get_ax_h($delivery,$reship_id);
		$ax_content_arr [] = $ax_h;

		$ax_d = $this->get_ax_d($delivery,$reship_id);
		$ax_content_arr [] = $ax_d;

		$ax_i = $this->get_ax_i($delivery);
		$ax_content_arr [] = $ax_i;  //发票功能暂时无法支持

		$az_l = $this->get_ax_l($delivery,$reship_id);
		$ax_content_arr [] = $az_l;
		
		$content = implode("\n",$ax_content_arr);
		return $content;
	}

	public function get_ax_h($delivery,$reship_id){
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

		$objReship = app::get('ome')->model('reship');
		$reship_bn = $objReship->getList('reship_bn,order_id,bcmoney',array('reship_id'=>$reship_id));
		$allReship = $objReship->getList('reship_id',array('order_id'=>$reship_bn[0]['order_id']));
		$nums =count($allReship);
		$ax_h[] = $delivery['order']['order_bn'].'-R'.$nums;//字段意思不明确，待定

		$ax_h[] = $delivery['order']['ax_order_bn'];//AX SO number
		
		$ax_h_customer_account = $ax_setting['ax_h_customer_account'];
		$ax_h[] = $ax_h_customer_account?$ax_h_customer_account:'C6002P1';// 固定参数  值待定
		
		$ax_h_invoice_ccount = $ax_setting['ax_h_invoice_ccount'];
		$ax_h[] = $ax_h_invoice_ccount?$ax_h_invoice_ccount:'C6002P1';//固定参数  值待定
		
		$ax_h_sales_order_status = app::get('omeftp')->getConf('ax_h_sales_order_status');
		$ax_h[] = $ax_h_sales_order_status?$ax_h_sales_order_status:'SEND_TO_ERP';// Sales order Status
		

		$reshipInfo = app::get('ome')->model('reship')->dump($reship_id,'memo,return_reason,return_type,custom_mark');
		if($reshipInfo['return_type']=="return"){
			$ax_h[] = $reshipInfo['memo']; //Sales Description
		}else{
			$memo=unserialize($reshipInfo['custom_mark']);
			$memo=$memo[0]['op_content'];
			if(empty($memo)){
				$ax_h[] = '';
			}else{
				$memo=str_replace("|","",$memo);
				$ax_h[] = $memo; //Sales Description
			}
		}
		
		$ax_h_currency = app::get('omeftp')->getConf('ax_h_currency');
		$ax_h[] = $ax_h_currency?$ax_h_currency:'CNY';// currency

		$ax_h[] = $reship_bn[0]['bcmoney']>0?sprintf("%1\$.2f",-($this->math->number_plus(array($reship_bn[0]['bcmoney'],0)))):'0.00';// freight amount 配送费用

		$ax_h[] = '0.00';//cod fee amount

		$ax_h[] = '0.00'; //total discount amount  优惠金额

		$ax_h[] = '';//total  discount %
		//MCD去掉换货明细
		$orderReship = app::get('ome')->model('reship_items');
		$reInfo = $orderReship->getList('*',array('reship_id'=>$reship_id,'return_type'=>'return'));
		foreach($reInfo as $ri){
			$itemNum += $ri['num'];
		}
		$ax_h[] = -intval($itemNum);//total quantity

		$ax_h[] = '';//Alt. delivery account
		$ax_h[] = date('Y-m-d H:i:s',$delivery['order']['createtime']);//date of order creation 订单创建时间

		$ax_h[] = '';//Language

		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = $reshipInfo['return_reason'];
		$ax_h[] = '';
		$ax_h[] = '';
		$ax_h[] = $delivery['order']['order_bn'].'-R'.$nums;
		
		return implode('|',$ax_h);
	}

	public function get_ax_d($delivery,$reship_id){
		$ax_d = array();
		
		$ax_d[] = 'D';

		$objReship = app::get('ome')->model('reship');
		$order_confirm_time = $objReship->getList('order_confirm_time',array('reship_id'=>$reship_id));
		$order_confirm_time = date('Y-m-d H:i:s',$order_confirm_time[0]['order_confirm_time']);

		$ax_d[] = '';//Requested receipt Date
		$ax_d[] = !empty($order_confirm_time)?$order_confirm_time:'';//Requested Ship Date
		$ax_d[] = '';//Confirmed receipt Date
		$ax_d[] = !empty($order_confirm_time)?$order_confirm_time:'';//Confirmed Ship Date

		$ax_d[] = '';//配送时间  暂时留空

		$ax_d[] = '';//Condition of Delivery   set by AX
		
		$ax_d_mode_of_delivery = app::get('omeftp')->getConf('ax_d_mode_of_delivery');
		if($delivery['consignee']['province']=='上海'||$delivery['consignee']['province']=='江苏省'||$delivery['consignee']['province']=='浙江省'||$delivery['consignee']['province']=='安徽省'||$delivery['consignee']['province']=='西藏自治区'){
			$ax_d[] = 'SF_STD';//
		}else{
			$ax_d[] = 'SF_SP';
		}

		$ax_d[] = '';//Packing Slip number
		$ax_d[] = '';//Shipping Date

		$ax_d[] = '';//Shipping Tracking URL
		$ax_d[] = '';//Shipping tracking ID

		/*
		$ax_d[] = $delivery['consignee']['name'];//Delivery Name
		$ax_d[] = $delivery['consignee']['district'].'==CR=='.$delivery['consignee']['addr'];//Delivery Street name
		$ax_d[] = $delivery['consignee']['zip'];//Delivery ZIP 
		$ax_d[] = $delivery['consignee']['city'];//Delivery City
		$ax_d[] = $delivery['consignee']['province'];//Delivery State ID
		$ax_d[] = 'CN';//Delivery Country/Region
		$ax_d[] = $delivery['consignee']['mobile'];//Delivery Contact
		$ax_d[] = '';//Order Total Weight
		*/

		//收货信息
		$ax_d[] = '';//Delivery Name
		$ax_d[] = '';//Delivery Street name
		$ax_d[] = '';//Delivery ZIP
		$ax_d[] = '';//Delivery City
		$ax_d[] = '';//Delivery State ID
		$ax_d[] = '';//Delivery Country/Region
		$ax_d[] = '';//Delivery Contact
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
		if($delivery['order']['is_tax']=='true'){
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

		return implode('|',$ax_i);
	}

	public function get_ax_l($delivery,$reship_id){
		$ax_l =$reInfo=array();
		$orderItemModel     = app::get('ome')->model('order_items');
		
		
		$orderReship = app::get('ome')->model('reship_items');
		//MCD去掉换货明细
		$reInfo = $orderReship->getList('*',array('reship_id'=>$reship_id,'return_type'=>'return'));

		$line = 0;
		foreach($reInfo as $key=>$reship_items){
			$develiy_items=array();
			$ax_pmt_price=0;
			$product_id=$reship_items['product_id'];
			
			$develiy_items      = $orderItemModel->getList('name, bn, nums as number,price,ax_pmt_price,ax_pmt_percent,pmt_price,amount,item_type,true_price', array('order_id'=>$delivery['order']['order_id'], 'delete'=>'false','product_id'=>$product_id),0,1);
			// 过滤发货单明细中的空格
			foreach((array)$develiy_items as $k=>$item){
				$delivery_items[$k] = array_map('trim', $item);
			}
		
			$ax_l[$key][] = 'L';
			if($delivery_items[0]['item_type']=='product'){
				$ax_l[$key][] = 'Sales';//SAP Item Type   eg.Sales  Gift  sample
			}elseif($delivery_items[0]['item_type']=='gift'){
				$ax_l[$key][] = 'Sample';//SAP Item Type   eg.Sales  Gift  sample
			}elseif($delivery_items[0]['item_type']=='sample'){
				$ax_l[$key][] = 'Gift';//SAP Item Type   eg.Sales  Gift  sample
			}else{
				$ax_l[$key][] = 'Sales';//SAP Item Type   eg.Sales  Gift  sample
			}
			$ax_l[$key][] = '';//AX SO line number
			$ax_l[$key][] = $line+1;//External SO line number
			$line++;
			$ax_l[$key][] = $reship_items['bn'];//Item Number
			$ax_l[$key][] = '';//Item description
			$ax_l[$key][] = '';//Text Detailled description of the item 
			$ax_l[$key][] = '';//External Item Code?? 需确认
			$ax_l[$key][] = '';//Bar code of the salable item  //条形码
			
			$ax_l[$key][] = '';//First line of the gift message
			$ax_l[$key][] = '';//Second line of the gift message
			$ax_l[$key][] = '';//Third line of the gift message
			$ax_l[$key][] = '';//Fourth line of the gift message
		
			$ax_l[$key][] = -$reship_items['num'];//Ordered quantity  Sales ordered quantity
			$ax_l[$key][] = $this->math->number_plus(array($delivery_items[0]['true_price'],0));//Sales Retail Price  Unit price on of the Sales Order Line
			$ax_l[$key][] = '';//Price unit  Price Unit of the Sales order Line
			
			
			$ax_l[$key][] = '0.00';//Discount amount
			$ax_l[$key][] = $this->math->number_plus(array($delivery_items[0]['ax_pmt_percent'],0));//Discount % 
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
            
            $ax_l[$key][] = '';//
            $ax_l[$key][] = '';//
            $ax_l[$key][] = 'NW';//site
            $ax_l[$key][] = '22-RTN';//warehouse

			$ax_l_str[$key] = implode('|',$ax_l[$key]);
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

                $order = $orderModel->dump(array('order_id'=>$order_id),'order_bn,shop_id,invoice_name,createtime,cost_tax,invoice_area,invoice_addr,invoice_zip,invoice_contact,is_tax,is_delivery,mark_text,sync,ship_area,order_id,self_delivery,createway');

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

                    $delivery['order'] = $orderModel->dump(array('order_id'=>$deliOrder['order_id']),'order_bn,ax_order_bn,cost_payment,shop_id,welcomecard,pmt_order,createtime,invoice_name,cost_tax,invoice_area,invoice_addr,invoice_zip,invoice_contact,is_tax,tax_company,cost_freight,is_delivery,mark_text,custom_mark,sync,ship_area,order_id,self_delivery,createway,is_w_card,pay_bn,message1,message2,message3,message4,message5,message6,discount,pmt_cost_shipping,total_amount');
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
		// 会员信息
        $memberModel = app::get('ome')->model('members');
        $delivery['member'] = $memberModel->dump(array('member_id'=>$delivery['member_id']),'uname,name');

        return $delivery;
	}


}