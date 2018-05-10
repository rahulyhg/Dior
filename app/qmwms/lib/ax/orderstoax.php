<?php
 
/**
 * Class qmwms_ax_orderstoax
 * 把当天的已发货的订单文件整合成一个大文件发送给AX
 * @payne.Wu 2018-01-12
 */
class qmwms_ax_orderstoax{

    public function __construct(&$app){
        $this->app = $app;

        $this->file_obj = kernel::single('omeftp_type_txt');
        $this->ftp_operate = kernel::single('omeftp_ftp_operate');
        $this->operate_log = kernel::single('omeftp_log');

        $this->math = kernel::single('eccommon_math');
    }


    public function sent_to_ax($date){

        $orderObject = app::get('ome')->model('orders');
        $ax_setting  = app::get('omeftp')->getConf('AX_SETTING');

        //所取订单的时间段(前一天晚上10点到今天晚上十点)
        if(!empty($date)){
            $date = strtotime($date);
        }else{
            $date = time();
        }
        $time = array();
        $time[]=mktime(22,0,0,date('m',$date),date('d',$date)-1,date('Y',$date));
        $time[]=mktime(22,0,0,date('m',$date),date('d',$date),date('Y',$date));
        $orderData   = $orderObject->getList('*',array('ship_status'=>1,'last_modified|between'=>$time));
        //$orderData   = $orderObject->getList('*',array('ship_status'=>1,'last_modified|between'=>$time),0,11);//调试语句


        //写入的文件名
        $file_brand = $ax_setting['ax_file_brand'];
        $file_prefix = $ax_setting['ax_file_prefix']?$ax_setting['ax_file_prefix']:'CN_ECO';
        $file_arr = array($file_prefix,$file_brand,'ORDER_TO_AX',date('Ymd',time()));
        $file_name = implode('_',$file_arr);
        //var_dump($file_name);exit;

        $file_params['file'] = ROOT_DIR.'/ftp/Testing/ax/'.$file_name.'.dat';
        $file_params['method'] = 'a';
        $file_params['data'] = $this->getContent($orderData,$file_params['file']);

        //文件写入日志
        $file_log_data = array(
            'content'=>$file_params['data']?$file_params['data']:'没有数据',
            'io_type'=>'in',
            'work_type'=>'ordertoax',
            'createtime'=>time(),
            'status'=>'prepare',
            'file_route'=>$file_params['file'],
        );
        $file_log_id = $this->operate_log->write_log($file_log_data,'file');
        $flag = $this->file_obj->toWrite($file_params,$msg);
        if($flag){
            $md5file = md5_file($file_params['file']);
            $md5_file_local = ROOT_DIR.'/ftp/Testing/ax/'.basename($file_params['file'],'.dat').'.bal';
            if(!file_exists($md5_file_local)){
                file_put_contents($md5_file_local,$md5file);
            }
            $this->pushfile($file_params['file'],$md5_file_local);
            $this->operate_log->update_log(array('status'=>'succ','lastmodify'=>time()),$file_log_id,'file');
        }else{
            $this->operate_log->update_log(array('status'=>'fail','memo'=>$msg),$file_log_id,'file');
        }

    }

    public function getContent($orders,$file){
        $ax_content_arr = array();
        if(file_exists($file)){

        }else{
            $ax_header = app::get('omeftp')->getConf('AX_Header');
            $ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
            $ax_content_arr[] = $ax_header;
        }
        //组装L行的数据
        $ax_h_l = $this->format_ax_h_l($orders);

        $ax_h = $this->get_ax_h($ax_h_l);
        $ax_content_arr [] = $ax_h;

        $ax_d = $this->get_ax_d($ax_h_l);
        $ax_content_arr [] = $ax_d;

        $ax_i = $this->get_ax_i($ax_h_l);
        $ax_content_arr [] = $ax_i;

        $ax_content_arr [] = $ax_h_l['ax_l'];

        $content = implode("\n",$ax_content_arr);
        return $content."\n";

    }

    public function format_ax_h_l($orders){
        $freight_amount = $total_discount_amount = $total_mount_incl_taxes = '0.00';
        $i = 0;
        $allOrderItems = array();

        foreach($orders as $key=>$value){
            $orderData = app::get('ome')->model('orders');
            $orderItems  = app::get('ome')->model('order_items');
            $ordersPrama = $orderData->dump($value['order_id']);
            $ordersPrama['order_items'] = $orderItems->getList('*',array('order_id'=>$value['order_id'],'delete'=>'false'));
            $freight = $this->math->number_plus(array(($ordersPrama['shipping']['cost_shipping']-$ordersPrama['pmt_cost_shipping']),0));
            $freight_amount += $freight;
            $total_discount_amount += $value['discount'];
            $total_mount_incl_taxes += $value['total_amount'];

            $allOrderItems[$key] = $ordersPrama['order_items'];
        }

        $itemsData = $this->format_order_items($allOrderItems);
        $return = $this->get_ax_l($itemsData,$i);
        $total_ordered_quantities = $return['quantities'];
        $ax_l[] = $return['ax_l_str'];

        $res['freight_amount'] = $freight_amount;
        $res['total_discount_amount'] = $total_discount_amount;
        $res['total_mount_incl_taxes'] = $total_mount_incl_taxes;
        $res['total_ordered_quantities'] = $total_ordered_quantities;
        $res['ax_l'] = implode("\n",$ax_l);
        return $res;
    }

    public function get_ax_h($orders){
        $ax_h = array();

        $seq = app::get('qmwms')->getConf('sqeuence');

        if(empty($seq)){
            app::get('qmwms')->setConf('sqeuence','0');
            $seq = app::get('qmwms')->getConf('sqeuence');
        }

        $ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
        $customer_requisition_number = $this->complete_length($seq,4,'0');

        $ax_h_h = $ax_setting['ax_h'];
        $ax_h[] = $ax_h_h?$ax_h_h:'H';// 1

        $ax_h_sales_country_code = $ax_setting['ax_h_sales_country_code'];
        $ax_h[] = $ax_h_sales_country_code?$ax_h_sales_country_code:'CN';// 2

        $ax_h_salas_division = $ax_setting['ax_h_salas_division'];
        $ax_h[] = $ax_h_salas_division?$ax_h_salas_division:'08';// 3

        $ax_h_sales_organization = $ax_setting['ax_h_sales_organization'];
        $ax_h[] = $ax_h_sales_organization?$ax_h_sales_organization:'2920';// 4

        $ax_h_plant = $ax_setting['ax_h_plant'];
        $ax_h[] = $ax_h_plant?$ax_h_plant:'1190';// 5

        $ax_h_customer_requisition_number = 'DM'.date('Ymd',time()).$customer_requisition_number;
        $ax_h[] = $ax_h_customer_requisition_number;// 6

        $ax_h[] = '';//AX SO Number 7

        $ax_h_customer_account = $ax_setting['ax_h_customer_account'];
        $ax_h[] = $ax_h_customer_account?$ax_h_customer_account:'C4013P1';// 固定参数 8

        $ax_h_invoice_ccount = $ax_setting['ax_h_invoice_ccount'];
        $ax_h[] = $ax_h_invoice_ccount?$ax_h_invoice_ccount:'C4013P1';//固定参数  9

        $ax_h_sales_order_status = $ax_setting['ax_h_sales_order_status'];
        $ax_h[] = $ax_h_sales_order_status?$ax_h_sales_order_status:'SEND_TO_ERP';// 10

        $ax_h[] = '';// sales Description  11

        $ax_h_currency = $ax_setting['ax_h_currency'];
        $ax_h[] = $ax_h_currency?$ax_h_currency:'CNY';// Currency  12

        $ax_h[] = number_format($orders['freight_amount'],3,'.','');// freight amount 13
        $ax_h[] = '';// COD Fee Amount 14
        $ax_h[] = number_format($orders['total_discount_amount'],3,'.','');// Total discount amount 15
        $ax_h[] = '';// Total discount % 16
        $ax_h[] = $orders['total_ordered_quantities'];// Total ordered quantities 17
        $ax_h[] = '';// Alt. delivery account 18
        $ax_h[] = date('Y-m-d H:i:s',time());// Ordering date @todo 暂时取值当前时间 19
        $ax_h[] = '';// EDI Buyer Customer 20

        $ax_h[] = '';// Language 21
        $ax_h[] = '';// Storage location 22
        $ax_h[] = '';// Address code 23
        $ax_h[] = '';// SAP Company Code 24
        $ax_h[] = '';// Vendor Code 25
        $ax_h[] = '';// Return reason 26
        $ax_h[] = '';// Free order reason 27
        $ax_h[] = '';// Cost center 28
        $ax_h[] = date('Ymd',time());// Customer reference  29 @todo 暂时取当前日期
        $ax_h[] = '';// BA Code 30

        $ax_h[] = '';// Applicant 31
        $ax_h[] = '';// Project 32

        return implode('|',$ax_h);

    }
    public function get_ax_d($orders){
        $ax_d = array();
        $ax_setting    = app::get('omeftp')->getConf('AX_SETTING');
        $ax_d[] = 'D';//1
        $ax_d[] = '';// Requested receipt Date 2
        $ax_d[] = date('Ymd',time());// Requested Ship Date  3 @todo 暂时取当前日期
        $ax_d[] = '';// Confirmed receipt Date 4
        $ax_d[] = date('Ymd',time());// Confirmed Ship Date  5 @todo 暂时取当前日期
        $ax_d[] = '';// Delivery Timing 6
        $ax_d[] = '';// Delivery Term 7
        $ax_d[] = '';// Mode of Delivery 8
        $ax_d[] = '';// Packing Slip number 9
        $ax_d[] = '';// Shipping Date 10

        $ax_d[] = '';// Shipping Tracking URL 11
        $ax_d[] = '';// Shipping tracking ID 12
        $ax_d[] = '';// Delivery Name  13
        $ax_d[] = '';// Delivery Street name 14
        $ax_d[] = '';// Delivery ZIP / Postal code 15
        $ax_d[] = '';// Delivery City 16
        $ax_d[] = '';// Delivery State ID 17
        $ax_d[] = '';// Delivery Country/Region 18
        $ax_d[] = '';// Delivery Contact 19
        $ax_d[] = '';// Order Total Weight 20

        $ax_d[] = '';// 3rd Party Id 21
        $ax_d[] = '';// 3rd Party Name 22
        $ax_d[] = '';// 3rd Party Street name 23
        $ax_d[] = '';// 3rd Party ZIP / Postal code 24
        $ax_d[] = '';// 3rd Party City 25
        $ax_d[] = '';// 3rd Party State ID 26
        $ax_d[] = '';// 3rd Party Country/Region 27
        $ax_d[] = '';// 3rd Party contact 28
        $ax_d[] = '';// Delivery From 29
        $ax_d[] = '';// Deliver Till 30

        $ax_d[] = '';// Advertise Date 31
        $ax_d[] = '';// Delivery Email 32
        $ax_d[] = '';// Recipient 33
        $ax_d[] = '';// Urgent order 34

        return implode('|',$ax_d);
    }
    public function get_ax_i($orders){
        $ax_i = array();

        $ax_i[] = 'I';//CSV Line Type 1
        $ax_i[] = '';//Bill to customer 2
        $ax_i[] = '';//Payment Term 3
        $ax_i[] = 'ALIPAY';//Method of payment 4
        $ax_i[] = '';//Invoice Number 5
        $ax_i[] = '';//Invoice date 6
        $ax_i[] = number_format($orders['total_mount_incl_taxes'],3,'.','');//Total Amount incl. taxes 7
        $ax_i[] = '';//Invoice  Name  8
        $ax_i[] = '';//Invoice  Street name  9
        $ax_i[] = '';//Invoice  ZIP / Postal code 10

        $ax_i[] = '';//Invoice City  11
        $ax_i[] = '';//Invoice  State ID  12
        $ax_i[] = '';//Invoice  Country/Region  13
        $ax_i[] = '';//Invoice Contact  14
        $ax_i[] = '';//Company legal form  15
        $ax_i[] = '';//Our Tax exempt number  16
        $ax_i[] = '';//Cust. Tax exempt number  17
        $ax_i[] = '';//Payment Term desc.  18
        $ax_i[] = '';//Payment due date  19
        $ax_i[] = '';//Discount date 20

        $ax_i[] = '';//Discount percent 21
        $ax_i[] = '';//Discount amount 22
        $ax_i[] = '';//Total Amount excl. Taxes 23
        $ax_i[] = '';//Total Sales taxes 24
        $ax_i[] = '';//Billing Email  25

        return implode('|',$ax_i);
    }
    public function get_ax_l($orders,&$i){
        $ax_l = $return = array();
        $j = 0;
        $orderObjModel = app::get('ome')->model('order_objects');
        foreach($orders['order_items'] as $key=>$order_items){
            $order_obj_items = $orderObjModel->dump($order_items['obj_id']);
            $ax_l[$key][] = 'L'; // 1
            if($order_obj_items['obj_type']=='goods'){
                $ax_l[$key][] = 'Sales';//SAP Item Type   2
            }elseif($order_obj_items['obj_type']=='gift'){
                $ax_l[$key][] = 'Sample';//SAP Item Type   2
            }elseif($order_obj_items['obj_type']=='sample'){
                $ax_l[$key][] = 'Gift';//SAP Item Type   2
            }else{
                $ax_l[$key][] = 'Sales';//SAP Item Type   2
            }
            $ax_l[$key][] = '';//AX SO line number 3
            $ax_l[$key][] = $j+1;//External SO line number  4
            $ax_l[$key][] = $order_items['bn'];//Item Number  5
            $ax_l[$key][] = '';//Item description  6
            $ax_l[$key][] = $order_items['name'];//Text 7
            $ax_l[$key][] = '';//External Item Code 8
            $ax_l[$key][] = '';//Bar Code  9
            $ax_l[$key][] = '';//Message line 1  10

            $ax_l[$key][] = '';//Message line 2  11
            $ax_l[$key][] = '';//Message line 3  12
            $ax_l[$key][] = '';//Message line 4  13
            $ax_l[$key][] = $order_items['nums'];//Ordered quantity  14
            $ax_l[$key][] = $order_obj_items['price'];//Ext Sys Price  15
            $ax_l[$key][] = '';//Price unit  16
            $ax_l[$key][] = number_format($order_items['ax_pmt_price']/$order_obj_items['quantity'],3,'.','');//Discount amount  17
            $ax_l[$key][] = '';//Discount %  18
            $ax_l[$key][] = '';//Discount % Level 1  19
            $ax_l[$key][] = '';//Discount % Level 2  20

            $ax_l[$key][] = '';//Discount % Level 3  21
            $ax_l[$key][] = '';//Shipped Qty   22
            $ax_l[$key][] = '';//Invoiced Qty  23
            $ax_l[$key][] = '';//Picking in progress Qty  24
            $ax_l[$key][] = '';//Picked Qty  25
            $ax_l[$key][] = 'Ea';//Item Sales Unit 26
            $ax_l[$key][] = '';//Total Discount Amount excl. Tax   27
            $ax_l[$key][] = '';//Discount label  28
            $ax_l[$key][] = '';//Line amount excl. Taxes   29
            $ax_l[$key][] = '';//Item Sales Tax Group  30

            $ax_l[$key][] = '';//Sales Tax rate  31
            $ax_l[$key][] = '';//Sales Tax amount  32
            $ax_l[$key][] = '';//Line amount incl. Taxes  33
            $ax_l[$key][] = '';//Batch Number   34
            $ax_l[$key][] = '';//Line Return reason  35
            $ax_l[$key][] = '';//Site  36
            $ax_l[$key][] = '';//Warehouse  37
            $ax_l[$key][] = '';//External Line Reference  38
            $ax_l[$key][] = '';//Delivery Order ID to WMS  39

            $ax_l_str[$key] = implode('|',$ax_l[$key]);
            $i += $order_items['nums'];
            $j += 1;
        }
        $return['ax_l_str'] = implode("\n",$ax_l_str);
        $return['quantities'] = $i;
        return $return;
    }

    /**
     * @param $orderItems
     * 重新整理订单明细信息
     * 汇总sku相同的商品明细
     */
    public function format_order_items($orderItems){

        $orderItem = $isExist = array();
        foreach($orderItems as $key=>$item){
            $orderItem = array_merge($orderItem,$item);
        }

        //取得符合条件的sku集合
        $sku_arr=array();
        $ax_pmt_price_exist = array();
        foreach($orderItem as $k=>$v){
            $ax_pmt_price =  intval($v['ax_pmt_price']);
            if(empty($ax_pmt_price)){
                $sku_arr[$k]=$v['bn'];
            }else{
                $ax_pmt_price_exist[$k] = $v;
            }
        }
        $sum=count($sku_arr);
        $after_sum=count(array_unique($sku_arr));
        if($sum==$after_sum){
            return $orderItem;
        }else{
            $a_dff=array_diff_key($sku_arr,array_unique($sku_arr));
            $a_val=array_values($a_dff);
            $array_sku=array();
            foreach($orderItem as $k=>$v){
                if(in_array($v['bn'],$a_val)){
                    $array_sku[$v['bn']]+=$v['nums'];
                }
            }
            foreach(array_unique($sku_arr) as $k=>$v){
                $new[$k]=$orderItem[$k];

                if(!empty($array_sku[$v])){
                    $new[$k]['nums']=$array_sku[$v];
                }
            }
            $new['order_items'] = array_merge($new,$ax_pmt_price_exist);
            return $new;
        }

    }

    public function complete_length($str,$length,$offset_str='0'){
        $str++;
        app::get('qmwms')->setConf('sqeuence',$str);
        $cur_length=strlen(trim((string)$str));
        if($cur_length<$length){
            $str=str_pad((string)$str,$length,(string)$offset_str,STR_PAD_LEFT);
        }
        return $str;
    }

    public function pushfile($dat,$bal){

        $params = array(
            'host'=>'122.144.198.46',
            'port'=>'22040',
            'name'=>'D1M_AX_PREPROD',
            'pass'=>'8qHTAlNn',
        );

        //连接ftp
        $this->conn = ssh2_connect($params['host'], $params['port']);
        if(ssh2_auth_password($this->conn, $params['name'],$params['pass'])){
           $this->sftp = @ssh2_sftp($this->conn);
        }else{
           echo "登录到FTP失败，请检查用户名和密码";
        }

        //上传数据
        if($this->sftp){
            $locals[] = $dat;
            $locals[] = $bal;
            foreach($locals as $k=>$local){
                $filename = basename($local);
                $remote_file = "/TO_AX/".$filename;
                $sftp = $this->sftp;
                $stream = @fopen("ssh2.sftp://$sftp$remote_file", 'w');
                $data_to_send = @file_get_contents($local);
                $size = @fwrite($stream, $data_to_send);
                @fclose($stream);
            }
        }else{
            echo "登录到FTP失败，请检查用户名和密码";
        }
    }


































}
?>