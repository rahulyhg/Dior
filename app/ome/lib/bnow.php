<?php
#大屏数据
class ome_bnow{
    function __construct(){
        include_once APP_DIR.'ome/statics/lib/oauth.php';
        $config['open']['key'] = 'ey527gjy';
        $config['open']['secret'] = 'kipzwtbbocj5pzlkjg66';
        $conf = $this->getconf();
        $config['open']['site'] = $conf['oauth2_prism_site'];
        $config['open']['oauth'] = $conf['oauth2_prism_oauth'];
        $this->open = new oauth2($config['open']); 
    }
    function process($type = null,$domain = null){
        #订单数据
        if($type == 'order'){
            $order_data = $this->getALLOrder();
            if(empty($order_data)){
                return false;
            }
            $fail_order = array();
            foreach($order_data as $order){
               $rs = $this->http_bnow_request($order,$type);
               if(is_array($rs)){
                   if(isset($rs['error'])){
                       $msg = $rs['error'];
                   }elseif(isset($rs['message'])){
                       $msg = $rs['message'];
                   }
               }else{
                   $msg = 'other error:'.(string)$rs;
               }
               if(isset($rs['result'])){
                   if($rs['result'] != 1){
                        $fail_order[] = $order['tid'].'=>'.$msg;
                    }
               }else{
                   $fail_order[] = $order['tid'].'=>'.$msg;
               }
            }
            if(!empty($fail_order)){
                $fail_order_bn = implode("||",$fail_order);
                #日志记录失败的订单
                $this->ilog($fail_order_bn,$domain,$type);
            }
        }
        #会员数据
        elseif($type == 'member'){
            $member_data = $this->getMemberInfo();
            if(empty($member_data)){
                return false;
            }
            $rs = $this->http_bnow_request($member_data,$type);
            if(is_array($rs)){
                if(isset($rs['error'])){
                    $msg = $rs['error'];
                }elseif(isset($rs['message'])){
                    $msg = $rs['message'];
                }
            }else{
                $msg = 'other error:'.(string)$rs;
            }
            if(isset($rs['result'])){
                if($rs['result'] != 1){
                    $fail_day = $member_data['time'];
                    $fail_day .= '=>'.$msg;
                    #日志记录失败的会员统计日期
                    $this->ilog($fail_day,$domain,$type);
                }
            }else{
                $fail_day = $member_data['time'];
                $fail_day .= '=>'.$msg;
                #日志记录失败的会员统计日期
                $this->ilog($fail_day,$domain,$type);
            }
        }
        return true;
    }
    #获取一段时间内的成交订单（已支付订单）
    function getALLOrder(){
        $obj_orders = &app::get('ome')->model('orders');
        $obj_order_items = &app::get('ome')->model('order_items');
    
        $last_runtime = &app::get("ome")->getConf("ome_bnow_last_runtime");#上次执行时间
        if(empty($last_runtime)){
            $last_runtime = strtotime(date('Y-m-d'));#如果是第一次执行，从当天0时开始
        }
        $time = time();
        $filter = array(
                'filter_sql'=>"createtime>=".$last_runtime.' and createtime<'.$time
        );
    
        $all_order_data = $obj_orders->getList('order_id,order_bn,createtime,total_amount,shop_id,ship_area',$filter);
        #查询完成，保存起来,做为下次查询的截止时间
        app::get("ome")->setConf("ome_bnow_last_runtime",$time);#当前时间
        if(empty($all_order_data)){
            return false;
        }
        $obj_shop = &app::get('ome')->model('shop');
        $order_info = array();
    
        $prodata_order = $_prodata_order = array();
        foreach($all_order_data as $v){
            $shop_info  = $obj_shop->dump(array('shop_id'=>$v['shop_id']),'node_id,node_type');
            $node_id = $shop_info['node_id'];
            $node_type = $shop_info['node_type'];
            if(empty($node_id)||empty($node_type)){
                $node_type = 'local';
                $node_id = 0;
            }
            #货品总数
            $item_nums = $obj_order_items->count(array('order_id'=>$v['order_id']));
    
            $_order_info['tid'] = $v['order_bn'];
            $_order_info['from_type'] = $node_type;
            $_order_info['from_nodeid'] = $node_id;
            $_order_info['amount'] = $v['total_amount'];
            $_order_info['prod_nums'] = $item_nums;
            $_order_info['time'] =  $v['createtime'];
            
            
            if($v['ship_area']){
                kernel::single('ome_func')->split_area($v['ship_area']);
                if($v['ship_area']){
                    $province = preg_replace('/省|市|壮族自治区|维吾尔自治区|特别行政区/','',$v['ship_area'][0]);
                    $_order_info['province'] = $this->getAreaId($province);
                }
            }
            $prodata_order[] = $_order_info;
       }
       return $prodata_order;
    }
    #当天的会员数据
    function getMemberInfo(){
       $last_day_time = strtotime('-1days');
       $day = date('Y-m-d',$last_day_time);#统计日期
       
       $memberModel = &app::get('ome')->model('members');
       $member_sum = $memberModel->count();
       $member_data['member_nums'] = $member_sum;#统计会员数
       $member_data['time'] = $day;#脚本执行时间是每天凌晨1:30,所以应该取前一天的时间
       
       return $member_data;
    }    
        
    function http_bnow_request($_param = null,$type = null){
        $node_id = base_shopnode::node_id('ome');
        $tg_version = $this->getVersion();
        
        if($type == 'order'){
            $_param['@class']='prodata-order';
        }elseif($type == 'member'){
            $_param['@class']='prodata-member';
        }
        $_param['shopexid'] = '';#可以不传
        $_param['nodeid'] = $node_id;
        $_param['product'] = 'C-0008';#产品线代码，这是写死的
        $_param['code'] = $tg_version;
        
        
        $params['data'] = json_encode($_param);
        $params['routing_key'] = 'bnow.stat.erp';#写死
        $params['content-type'] = 'application/json';#写死
        
        
        error_reporting(0);#禁用错误报告
        $r = $this->open->request()->get('api/platform/timestamp');
        $time = $r->parsed();
        $this->open->request()->timeout = 10;
        
        $api = 'api/platform/notify/publish';
        $_result = $this->open->request()->post($api, $params,$time);
        $results = $_result->parsed(); 
        return $results;
    }
    #获取版本
    function getVersion(){
        $codes = array(
                'pro'=>'product_0060',#旗舰版,
                'basic' =>'product_0059',#企业版
                'tperp'=>'product_0200' #协同版
        );
        $tg_version =  app::get('ome')->getConf('tg_version');
        if(empty($tg_version)){
            $tg_version = 'tperp';#tperp
        }
		$tg_version = 'tperp';#这条线写死为tperp
        return $codes[$tg_version];
    }
    #应用协议配置
    function getconf(){
        $setting = array(
                'oauth2_prism_site' => 'https://openapi.ishopex.cn',
                'oauth2_prism_oauth' => 'https://oauth.ishopex.cn',
        );
        return $setting;
    }
    function ilog($str = null,$domain = null,$type = null) { 
        $filename = ROOT_DIR.'/script/update/logs/bnow_'.$type.'_' . date('Y-m-d') . '.log';
        $fp = fopen($filename, 'a');
    
        fwrite($fp, date("m-d H:i") . "\t" . $domain . "\t" . $str . "\n");
        fclose($fp);
    }
    function getAreaId($area_name = false){
        //$state = str_replace(array('省','自治区','市','壮族自治区','回族自治区','维吾尔自治区','特别行政区'),'',$area_name);
        $area =  array(
                '北京' => 110000,
                '天津' => 120000,
                '河北' => 130000,
                '山西' => 140000,
                '内蒙古' => 150000,
                '辽宁' => 210000,
                '吉林' => 220000,
                '黑龙江' => 230000,
                '上海' => 310000,
                '江苏' => 320000,
                '浙江' => 330000,
                '安徽' => 340000,
                '福建' => 350000,
                '江西' => 360000,
                '山东' => 370000,
                '河南' => 410000,
                '湖北' => 420000,
                '湖南' => 430000,
                '广东' => 440000,
                '广西' => 450000,
                '海南' => 460000,
                '重庆' => 500000,
                '四川' => 510000,
                '贵州' => 520000,
                '云南' => 530000,
                '西藏' => 540000,
                '陕西' => 610000,
                '甘肃' => 620000,
                '青海' => 630000,
                '宁夏' => 640000,
                '新疆' => 650000,
                '台湾' => 710000,
                '香港' => 810000,
                '澳门' => 820000,
                '海外' => 990000
        ); 
        return $area[$area_name];
    }
}