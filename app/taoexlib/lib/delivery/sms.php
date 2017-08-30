<?php
/**
 * @description 发货发送短信
 * @access public
 * @param void
 * @return void
 */
class  taoexlib_delivery_sms
{

    /*
    * 发货并且发短信提醒
    */
    public function deliverySendMessage($logi_no){
        $switch=app::get("taoexlib")->getConf('taoexlib.message.switch');
        if($switch == 'on'){
            $info = $this->getLogiNoInfo($logi_no);
            if($info){
                //$phone = trim($info[1][12]);
                //$delivery_bn = $info[1][11];
                $phone = $info['replace']['ship_mobile'];
                $delivery_bn = $info['replace']['delivery_bn'];
                $messcontent = $info['content'];
                if(!empty($phone)){
                    if($this->checkBlackTel($phone)){
                        
                        $this->sendOne($phone,$info,$logi_no,$delivery_bn);
                    }else{
                        $this->writeSmslog($phone,$messcontent,'该手机号处于免打扰列表中',0);
                    }
                }
            }
        }
    }

    /*
     * 通过物流单号 获取信息
     * 如果是false 说明系统中没有查找的快递单号
     * 1,和短信设置中的信息匹配替换
     * 2,将获得的数据一并返回 方便后面短信发送需要提取个性化信息
     */
     public function getLogiNoInfo($logi_no, $content=NULL){
        $deliveryinfo = app::get('ome')->model('delivery');
        $rule_sample_mdl = app::get('taoexlib')->model('sms_bind');
        
        $info = $deliveryinfo->dump(array('logi_no|nequal' => $logi_no),'*',array('delivery_items'=>array('*'),'delivery_order'=>array('*'),'shop'=>array('*')));
        
        //物流单号系统中不存在
        if(empty($info)){ return false; }

        if(!$content){
            $contentinfo = $rule_sample_mdl->getSmsContentByRuleId($info['sms_group']);

            if (!$contentinfo['content']) {
                return false;
            }else{
                $content = $contentinfo['content'];
            }
        }else{

        }
        //获取匹配信息区域

        //《发货单号$delivery_bn》
        $delivery_bn=$info["delivery_bn"];

        //《订单号$orderstr》如果多个订单号使用逗号隔开
        $ordersObj = app::get('ome')->model('orders');
        $order_id = array_keys($info['delivery_order']);
        $orders = $ordersObj->getList('order_id, order_bn, status, ship_status, process_status,total_amount,payed', array('order_id|in' => $order_id));
        $i = 0;
        foreach ($orders as $os) {
            if($i>0){ $orderstr .=','; }
            $orderstr .=$os['order_bn'];
            $i++;
            //《订单金额$total_amount》
            $total_amount += $os['total_amount'];
            //《实际付款总金额$payed》
            $payed += $os['payed'];
        }
                    
        //《订单优惠金额$cheap》
        $cheap = $total_amount-$payed;
                    
        //店铺信息:
        $shopinfo = app::get('ome')->model('shop');
        $shopinfoarr = $shopinfo->dump(array('shop_id|nequal' => $info['shop_id']),'*');
        
        //《店铺名$shopname》
        $shopname = $shopinfoarr['name'];
                    
        //会员信息
        $membersinfo = app::get('ome')->model('members');
        $membersinfoarr = $membersinfo->dump(array('member_id|nequal' => $info['member_id']),'*');
        
        //《会员名$uname》
        $uname = $membersinfoarr['account']['uname'];
                    
        //《物流费用$logi_actual》
        $logi_actual = $info['delivery_cost_actual'];
        if($logi_actual == '0'){
            $logi_actual='包邮';
        }
                    
        //《收货人$ship_name》
        $ship_name = $info['consignee']['name'];
        
        //《收货人手机号码$ship_mobile》
        $ship_mobile = $info['consignee']['mobile'];
                    
        //《物流公司$logi_name》
        $logi_name = $info['logi_name'];
                    
        //《物流单号$logi_no》
        $logi_no = $info['logi_no'];
                    
        //《发货时间$delivery_time》
        $delivery_time = date("d日 H点i分",$info['delivery_time']);
        //订单创建时间
        $create_time   = date("d日 H点i分",$info['create_time']);        
        //$find 和 $replace 一一对应，需要增加删除修改，修改对应的做改动
        $find = array('{会员名}','{收货人}','{店铺名称}','{物流公司}','{物流单号}','{发货时间}','{配送费用}','{订单号}','{订单金额}','{付款金额}','{订单优惠}','{发货单号}','{收货人手机号}','{订单时间}','{短信签名}');
        $replace = array($uname,$ship_name,$shopname,$logi_name,$logi_no,$delivery_time,$logi_actual,$orderstr,$total_amount,$payed,$cheap,$delivery_bn,$ship_mobile,$create_time,$msgsign);
                    
        //$content为短信配置中的模板信息
       //$content = $this->app->getConf('taoexlib.message.samplecontent');
        $messcontent['tplid'] = $contentinfo['tplid'];
        $messcontent['replace'] = array(
            'uname' =>$uname,
            'ship_name'   =>$ship_name,
            'shopname'=>$shopname,
            'logi_name'=>$logi_name,
            'logi_no'=>$logi_no,
            'delivery_time'=>$delivery_time,
            'logi_actual'=>$logi_actual,
            'orderstr'=>$orderstr,
            'total_amount'=>$total_amount,
            'payed'=>$payed,
            'cheap'=>$cheap,
            'delivery_bn'=>$delivery_bn,
            'ship_mobile'=>$ship_mobile,
            'create_time'=>$create_time,
            'msgsign'=>"【".$shopname."】",
        );
        
        //将获取的值和模板中的定义的变量替换
        $messcontent['content'] = str_replace($find,$replace,$content);
        //组合数组:为了获取个别信息做准备 $messarr[0]：为组合的数据 $messarr[1][0...9]:为个别数据
        //$messarr[] = $messcontent;
        //$messarr[] = $replace;            
        //返回给ajax成功
        return $messcontent;

     }

    /*
     * 检测是否在免打扰手机号列表中
     * 将手机号放进去验证，检查该手机号是否处于免打扰列表中
     */
     public function checkBlackTel($tel){
        $blacklist=app::get('taoexlib')->getConf("taoexlib.message.blacklist");
        $blarr=explode("##",$blacklist);
        if(!in_array($tel,$blarr)){
            return true;
        }else{
            return false;
        }
     }

    /*
    * sendOne:发送短信
    * @param $phone='13838385438'
    * @param $content string;
    * @param $echostr 是否开启输出功能 预览的时候开启返回短信状态信息 关闭将不再显示短信状态信息 用于发货 可以到短信日志查看日志状态信息
    */
    public function sendOne($phone,$content,$logi_no,$delivery_bn,$echostr=false) {
        base_kvstore::instance('taoexlib')->fetch('account', $account);
         if (!unserialize($account)) { return false; }
        $param = unserialize($account);
        $info = taoexlib_utils::get_user_info($param);
        //短信签名验证
//        preg_match('/\【(.*?)\】$/',$content['content'],$filtcontent1);
//        if ($filtcontent1) {
//            kernel::single('taoexlib_request_sms')->newoauth_request(array('sms_sign'=>$filtcontent1[0]));
//        }
        if ('succ' == $info->res) {
            if ($info->info->month_residual) {
                $mscontent =array(
                  'phones' => $phone,
                  'replace' => $content['replace'],
                  'tplid'  =>$content['tplid'],
                  'content'=>$content['content'],
                );

                $smsresult=taoexlib_utils::send_notice($param, $mscontent);
                
                if($echostr&&$smsresult)
                    return 'sendOk';
                else
                    return 'sendFalse';
            }else{
                $this->writeSmslog($phone,$content['content'],'当前没有可用的短信条数！',0);
                
                if($echostr) return 'month_residual_zero';
            }
        }else{
            $this->writeSmslog($phone,$content,$info->info,0);

            if($echostr) return '发送失败，原因：'.$info->info.'！';
        }
    }

    /*
    * wujian@shopex.cn
    * 短信日志
    * 2012年2月21日
    * @param $phonearr 电话号码
    * @param $delivery_bn 发货单号
    * @param $logo 快递单号
    * @param $content 发送内容
    * @param $msg 短信状态信息
    * @param $status 短信状态
    */
    public function writeSmslog($phone,$content,$msg,$status){
        $messlog = app::get('taoexlib')->model("log");
        $messlogdata = array(
            'mobile'=>$phone,
            'batchno'=>'',
            'content'=>$content,
            'sendtime'=>time(),
            'msg'=>$msg,
            'status' =>$status,
        );

        $messlog->insert($messlogdata);		
    }
}