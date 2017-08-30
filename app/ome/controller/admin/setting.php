<?php
class ome_ctl_admin_setting extends desktop_controller{
    var $name = "基本设置";
    var $workground = "setting_tools";

    private $tabs = array(
        'order' => '订单配置',
        'purchase' => '仓储采购',
        'preprocess' => '预处理配置',
        'other' => '其他配置',
    );

    private function _comp_setting($arr1,$arr2){
        if($arr1["order"] == $arr2["order"])return 0;return $arr1["order"] > $arr2["order"] ? 1 : -1;
    }
    public function index(){
        $opObj    = &app::get('ome')->model('operation_log');#配置修改日志
        
        //配置信息保存
        if($_POST['set']){
            $settins = $_POST['set'];
            $this->begin();
            if($settins['ome.product.serial.merge']=='true' && !empty($settins['ome.product.serial.separate'])){
                $settins['ome.product.serial.separate'] = trim($settins['ome.product.serial.separate']);
                if(strlen($settins['ome.product.serial.separate'])>1){
                    $this->end(false,'分隔符只允许是一个字符');
                }
                if(preg_match("/([a-zA-Z]{1}|[0-9]{1})/i", $settins['ome.product.serial.separate'])){
                    $this->end(false,'分隔符不允许是字母或数字');
                }
                $productObj = &$this->app->model("products");
                $filter['barcode|has'] = $settins['ome.product.serial.separate'];
                $checkInfo = $productObj->dump($filter,'product_id,barcode');
                if($checkInfo['product_id']>0){
                    $this->end(false,'现有商品条形码中存在此分隔符');
                }
            }else{
                unset($settins['ome.product.serial.separate']);
            }

           if(!isset($settins['ome.combine.addressconf']['ship_address']) && !isset($settins['ome.combine.addressconf']['mobile'])) {
                $this->end(false,'相同地址判定中,收货地址和手机至少选择一个!');
            }
            
            //自动审单配置 ExBOY
            $old_is_auto_combine    = $this->app->getConf('ome.order.is_auto_combine');
            $now_is_auto_combine    = $settins['ome.order.is_auto_combine'];
            
            if($old_is_auto_combine != $now_is_auto_combine)
            {
                if($now_is_auto_combine == 'true')
                {
                    $log_msg   = '开启自动审单';
                }
                else
                {
                    $log_msg   = '关闭自动审单';
                }
                $opObj->write_log('order_split@ome', 0, $log_msg);
            }
            
            //复审配置
            if( !isset($settins['ome.order.retrial']['product'])){
                $settins['ome.order.retrial']['product'] = 0;
            }
            if( !isset($settins['ome.order.retrial']['order'])){
                $settins['ome.order.retrial']['order'] = 0;
            }
            if( !isset($settins['ome.order.retrial']['delivery'])){
                $settins['ome.order.retrial']['delivery'] = 0;
            }
            
            if( !isset($settins['ome.order.cost_multiple']['flag'])){
                $settins['ome.order.cost_multiple']['flag'] = 0;
            }
            if( !isset($settins['ome.order.sales_multiple']['flag'])){
                $settins['ome.order.sales_multiple']['flag'] = 0;
            }
            
            foreach($settins as $set=>$value){
                $curSet = $this->app->getConf($set);
                if($curSet!=$settins[$set]){
                    $curSet = $settins[$set];
                    $this->app->setConf($set,$settins[$set]);
                }
            }

            if(!isset($settins['ome.combine.addressconf']['ship_address'])){
                $settins['ome.combine.addressconf']['ship_address'] = 1;
            }

            if( !isset($settins['ome.combine.addressconf']['mobile'])){
                $settins['ome.combine.addressconf']['mobile'] = 1;
            }
            if($settins['ome.delivery.weight'] == 'on'){
               $this->app->setConf('ome.delivery.check_delivery','off');#称重开启后，关闭校验完即发货功能
             }

            //如果提交的内容值有变化才更新
            // foreach($settins as $set=>$value){
            //     $curSet = app::get('ome')->getConf($set);
            //     if($curSet!=$settins[$set]){
            //         $curSet = $settins[$set];
            //         app::get('ome')->setConf($set,$settins[$set]);
            //     }
            // }

            //库存成本保存
            // if($settins['ome.delivery.weight'] == 'off'){
            //     $this->app->setConf('ome.delivery.logi','0');
            // }
            if($_POST['extends_set']){
                foreach(kernel::servicelist('system_setting') as $k=>$obj){
                    if(method_exists($obj,'save')){
                       if($obj->save($_POST['extends_set'],$msg) === false) $this->end(false,$msg);
                    }
                }
            }

            //扩展配置信息保存
            foreach(kernel::servicelist('system_setting') as $k=>$obj){
                if(method_exists($obj,'saveConf')){
                    $obj->saveConf($settins);
                }
            }

            $this->end(true,'保存成功');
        }

        // 系统配置显示
        //$settingTabs = array(
        //    array('name' => '订单配置', 'file_name' => 'admin/system/setting/tab_order.html', 'app' => 'ome'),
        //    array('name' => '仓储采购', 'file_name' => 'admin/system/setting/tab_storage.html', 'app' => 'ome'),
        //    array('name' => '发货校验', 'file_name' => 'admin/system/setting/tab_delivery.html', 'app' => 'ome'),
        //    array('name' => '预处理配置', 'file_name' => 'admin/system/setting/tab_preprocess.html', 'app' => 'ome'),
        //    array('name' => '订单复审设置', 'file_name' => 'admin/system/setting/tab_retrial.html', 'app'=>'ome', 'order' => 30),
        //    array('name' => '其他配置', 'file_name' => 'admin/system/setting/tab_other.html', 'app'=>'ome'),
        //);
        $settingTabs = array();
        $setData = array();
        // $setView = array();

        // 读取所有可配置项
        $setting_info = array();

        //其他的配置暂时不动，直接赋值，后面细分到具体app
        // $show_tabs = $this->tabs;

        $servicelist = kernel::servicelist('system_setting');
        
        //配置信息的加载
        foreach($servicelist as $k=>$obj){

            //顶部tab页
            // if(isset($obj->tab_key) && isset($obj->tab_name)){
            //     $show_tabs = array_merge($show_tabs,array($obj->tab_key=>$obj->tab_name));
            // }

            //具体配置参数
            if(method_exists($obj,'all_settings')){
                $setting_info = array_merge($setting_info,$obj->all_settings());
            }

            if (method_exists($obj, 'get_setting_tab')) {
                $settingTabs = array_merge($settingTabs, $obj->get_setting_tab());
            }

            if (method_exists($obj,'get_pagedata')) {
                $obj->get_pagedata($this);
            }

            if (method_exists($obj,'get_setting_data')) {
                $setData = array_merge($setData,$obj->get_setting_data());
            }
        }

        uasort($settingTabs,array($this,'_comp_setting'));

        // 获取配置项值
        // foreach($setting_info as $set){
        //     $key = str_replace('.','_',$set);
        //     $setData[$key] = app::get('ome')->getConf($set);
        // }
        //因为老数据的问题，扩展的信息赋值放在全局赋值后面
        // foreach($servicelist as $k=>$obj){
        //     if(method_exists($obj, 'getView')){
        //         $setView[] = $obj->getView();
        //     }
        // }
        // if($_GET['pos']){
        //     $this->pagedata['display_pos'] = $_GET['pos'];
        // }
        #快递单与称重的顺序标示
        // if(!isset($setData['ome_delivery_logi'])){
        //     $setData['ome_delivery_logi'] = '0';
        // }
        
        // if($_GET['pos']){
        //     $this->pagedata['display_pos'] = $_GET['pos'];
        // }
        #快递单与称重的顺序标示
        // if(!isset($setData['ome_delivery_logi'])){
        //     $setData['ome_delivery_logi'] = '0';
        // }
        #逐单校验后即发货,默认是关闭的
        if(!isset($setData['ome_delivery_check_delivery'])){
            $setData['ome_delivery_check_delivery'] = 'off';
        }
        #称重开启，校验完即发货功能,默认是关闭的
        if($settins['ome.delivery.weight'] == 'on'){
            $setData['ome_delivery_check_delivery'] = 'off';
        }
        #华强宝默认是开启的
        if(!isset($setData['ome_delivery_hqepay'])){
            $setData['ome_delivery_hqepay'] = 'true';
        }

        $this->pagedata['settingTabs'] = $settingTabs;
        $this->pagedata['setData'] = $setData;
        $this->pagedata['branchCount'] = count(app::get('ome')->model('branch')->Get_branchlist());
        // $this->pagedata['setView']=$setView;
        $this->pagedata['show_tabs'] = $show_tabs;
        $this->page("admin/system/setting_index_all.html");
    }

    function app_list(){
        $rows = kernel::database()->select('select app_id,app_name from sdb_base_apps where status = "active"');
        $app_list = array();
        foreach($rows as $v){
           $app_list[] = $v['app_id'];
        }
        return $app_list;
    }
     /*
     * 订单异常类型设置
     */
    function abnormal(){
        $this->finder('ome_mdl_abnormal_type',array(
            'title'=>'订单异常类型设置',
            'actions'=>array(
                            array(
                                'label'=>'添加',
                                'href'=>'index.php?app=ome&ctl=admin_setting&act=addabnormal',
                                 'target' => 'dialog::{width:450,height:150,title:\'新建异常类型\'}'
                            ),
                        ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
         ));
    }
    /*
    * 添加订单异常类型
    */
    function addabnormal(){
        $oAbnormal = &$this->app->model("abnormal_type");
        if($_POST){
            $this->begin('index.php?app=ome&ctl=admin_setting&act=abnormal');
            $oAbnormal->save($_POST['type']);
            $this->end(true, app::get('base')->_('保存成功'),3);
        }
        $this->pagedata['title'] = '添加订单异常类型';
        $this->page("admin/system/abnormal.html");
    }
    /*
    * 编辑订单异常类型
    */
    function editabnormal($type_id){
        $oAbnormal = &$this->app->model("abnormal_type");
        $this->pagedata['abnormal']=$oAbnormal->dump($type_id);
        $this->pagedata['title'] = '编辑订单异常类型';
        $this->page("admin/system/abnormal.html");
    }
     /*
     * 售后问题类型设置
     */
    function product_problem(){//return_product_problem
        $this->finder('ome_mdl_return_product_problem',array(
            'title'=>'售后问题类型设置',
            'actions'=>array(
                            array(
                                'label'=>'添加',
                                'href'=>'index.php?app=ome&ctl=admin_setting&act=addproblem',
                                'target' => 'dialog::{width:450,height:150,title:\'新建售后问题类型\'}',
                            ),
                        ),
            'use_buildin_filter'=>true,
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
         ));
    }

    /*
     * 添加售后问题
     */
    function addproblem(){
        $oProblem = &$this->app->model("return_product_problem");
        if($_POST){
            $this->begin('index.php?app=ome&ctl=admin_setting&act=product_problem');
            $oProblem->save($_POST);
            $this->end(true, app::get('base')->_('添加成功'),3);
        }
        $this->pagedata['disabled_type'] = array('true'=>'是','false'=>'否');
        $this->pagedata['problem']['disabled'] = 'false';
        $this->page("admin/system/product_problem.html");
    }
    /*
     * 编辑售后问题
     */
    function editproblem($problem_id){
        $oProblem = &$this->app->model("return_product_problem");
        $problem = $oProblem->dump($problem_id);
        $this->pagedata['problem'] = $problem;
        $this->pagedata['disabled_type'] = array('true'=>'是','false'=>'否');
        $this->page("admin/system/product_problem.html");
    }

}
?>
