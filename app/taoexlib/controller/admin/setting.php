<?php
class taoexlib_ctl_admin_setting extends desktop_controller {
    var $workground = 'rolescfg';
    public function __construct($app) {
        if (!defined('APP_TOKEN') || !defined('APP_SOURCE')) {
            echo "请在Config文件中定义常量APP_TOKEN和APP_SOURCE";
            exit;
        }
        parent::__construct($app);
    }
    
    /*
     * 2012年2月16日
     * wujian@shopex.cn
     * 作用：短信设置：(1)、发货提醒开关设置；(2)、剩余短信预警条数
     */
    public function index() {
        if($_POST['set']){
            $settins = $_POST['set'];
            $this->begin('index.php?app=taoexlib&ctl=admin_setting&act=index');

            foreach($settins as $set=>$value){
                $curSet = $this->app->getConf($set);
                if($curSet!=$settins[$set]){
                    $curSet = $settins[$set];
                    $this->app->setConf($set,$settins[$set]);
                }
            }
            $this->end(true,'保存成功');
        }
        $setView = array();
        foreach(kernel::servicelist('message_setting') as $k=>$obj){
            if(method_exists($obj,'view')){
                $setView[] = $obj->view();
            }
        }
        $this->pagedata['setView']=$setView;
        $this->page("admin/setting_index.html");
    }
     
    /*
     * 2012年2月16日
     * wujian@shopex.cn
     * 作用：短信模板设置，短信内容设置
     */
     
     public function sample(){
         if($_POST['set']){
            $settins = $_POST['set'];
            $this->begin('index.php?app=taoexlib&ctl=admin_setting&act=sample');

            foreach($settins as $set=>$value){
                $curSet = $this->app->getConf($set);
                if($curSet!=$settins[$set]){
                    $curSet = $settins[$set];
                    $this->app->setConf($set,$settins[$set]);
                }
            }
            $this->end(true,'保存成功');
        }
        $setView = array();
        foreach(kernel::servicelist('message_setting') as $k=>$obj){
            if(method_exists($obj,'view')){
                $setView[] = $obj->view();
            }
        }
        $this->pagedata['setView']=$setView;
         $this->page("admin/sample.html");
     }
     
    /*
     * 2012年2月17日
     * wujian@shopex.cn
     * 作用：短信预览
     */
     
     public function preview(){
         $this->pagedata['messagecontent']=$_POST['content'];
         $this->pagedata['id'] = $_POST['id'];
        $this->display("admin/preview.html");
     }
     
     /*
      * 短信预览发送
      */
     public function sendtest(){
        $mobile = $_POST['mobile'];
        $logi_no = $_POST['logi_no'];
        $id = $_POST['id'];
        $messcontent = $_POST['remark'];
        $oSample_items = app::get('taoexlib')->model('sms_sample_items');
        if($this->checkBlackTel($mobile)){
            $info = kernel::single('taoexlib_delivery_sms')->getLogiNoInfo($logi_no, $messcontent);

            unset($info['content']);
            //获取最新的模板
            $items = $oSample_items->getlist('tplid',array('id'=>$id,'approved'=>'1'),0,1,'iid DESC');
            $items = $items[0];
            $info['content'] = $messcontent;
            $info['tplid'] = $items['tplid'];
            if($info){
                $str = kernel::single('taoexlib_delivery_sms')->sendOne($mobile,$info,$logi_no,$info[1][11],true);
            }else{
                $str = 'loginoerror';
            }
        }else{
            //手机处于免打扰
            $str = "BlackMobile";
        }
        echo json_encode($str);
     }
     
     
     /*
      * 检测物流单号是否存在
      * 如果没有存在提示错误
      * 如果存在 则返回值替换短信内容
      */
     public function checkReplace(){
        $logi_no = $_POST['logi_no'];
        $content = $_POST['content'];
        //内容检查
        if (empty($content)) {
            echo json_encode('content_no_empty');
            exit;
        }
        //传过来的快递单号值为空
        if(empty($logi_no)){
            echo json_encode('logi_no_empty');
            exit;
        }   
        $info = $this->getLogiNoInfo($logi_no, $content);
        if($info){
            echo json_encode($info[0]);
        }else{
            //物流单号系统中不存在
            echo json_encode('logi_info_empty');
        }
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
        if(empty($info)){
            return false;
        }
        if(!$content){
            $contentinfo = $rule_sample_mdl->getSmsContentByRuleId($info['sms_group']);
            if (!$contentinfo['content']) {
                return false;
            }else{
                $content = $contentinfo['content'];
            }
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
        if($i>0){
            $orderstr .=',';
        }
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
        $msgsign = "【".$shopname."】";
        //$find 和 $replace 一一对应，需要增加删除修改，修改对应的做改动
        $find = array('{会员名}','{收货人}','{店铺名称}','{物流公司}','{物流单号}','{发货时间}','{配送费用}','{订单号}','{订单金额}','{付款金额}','{订单优惠}','{发货单号}','{收货人手机号}','{订单时间}','{短信签名}');
        $replace = array($uname,$ship_name,$shopname,$logi_name,$logi_no,$delivery_time,$logi_actual,$orderstr,$total_amount,$payed,$cheap,$delivery_bn,$ship_mobile,$create_time,$msgsign);
                    
        //$content为短信配置中的模板信息
        #$content = $this->app->getConf('taoexlib.message.samplecontent');
                    
        //将获取的值和模板中的定义的变量替换
        $messcontent = str_replace($find,$replace,$content);
        
        //组合数组:为了获取个别信息做准备 $messarr[0]：为组合的数据 $messarr[1][0...9]:为个别数据
        $messarr[] = $messcontent;
        $messarr[] = $replace;            
        //返回给ajax成功
        return $messarr;

     }
     
     
     public function blacklist(){
         if($_POST['set']){
            $settins = $_POST['set'];
            $this->begin('index.php?app=taoexlib&ctl=admin_setting&act=blacklist');

            foreach($settins as $set=>$value){
                $curSet = $this->app->getConf($set);
                if($curSet!=$settins[$set]){
                    $curSet = $settins[$set];
                    $this->app->setConf($set,$settins[$set]);
                }
            }
            $this->end(true,'保存成功');
        }
        $setView = array();
        foreach(kernel::servicelist('message_setting') as $k=>$obj){
            if(method_exists($obj,'view')){
                $setView[] = $obj->view();
            }
        }
        $this->pagedata['setView']=$setView;
         $this->page("admin/blacklist.html");
     }     
}