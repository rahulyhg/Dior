<?php
class ome_finder_shop{
    var $detail_shop = "前端店铺详情";
    var $detail_config = "前端店铺同步";
    //增加前端店铺回写设置
    //var $detail_request = "前端回写设置";
    var $detail_dly_corp = "前端物流绑定";
    var $detail_shoporderlist = "前端店铺检查";

    function detail_shop($shop_id){
        $render = app::get('ome')->render();
        $oShop = &app::get('ome')->model("shop");
        $shop = $oShop->dump($shop_id);
        $shop_type = ome_shop_type::get_shop_type();
        $reset_login = "";
        $shoptype = $shop['node_type'];
        $node_id = $shop['node_id'];
        $finder_id = $_GET['_finder']['finder_id'];
        $taobao_session = &app::get('ome')->getConf('taobao_session_'.$node_id);
        $taobao_session = strval($taobao_session);
        $certi_id = base_certificate::get('certificate_id');
        $url = OPENID_URL."?open=taobao&certi_id=".$certi_id."&node_id=".$node_id."&refertype=ecos.ome&callback_url=http://".$_SERVER['HTTP_HOST'].kernel::base_url()."/index.php/api";

        if ($shoptype=='taobao' and $node_id and ($taobao_session=='true' or $taobao_session==1)){
            //$reset_login = '<a href="'.$url.'" target="_blank"><b>重新登录</a></a>';
        }
        $render->pagedata['reset_login'] = $reset_login;
        $render->pagedata['shop']=$shop;
        $render->pagedata['finder_id']=$finder_id;
        $render->pagedata['shop_type'] = $shop_type;
        return $render->fetch("admin/system/terminal_detail.html");

    }

    /**
     *前端店铺同步
     *
     * @param String $shop_id
     */
    function detail_config($shop_id){
        $render = app::get('ome')->render();
        $shop = app::get('ome')->model('shop')->getList('node_type,node_id,business_type',array('shop_id'=>$shop_id));
        
        if($shop[0]['node_id']){
            $config['is_config'] = ome_shop_type::get_shoporder_config($shop[0]['node_type']);
            $config['error_msg'] = '本店铺暂不支持同步前端订单';
        }else{
            $config['is_config'] = 'off';
            $config['error_msg'] = '店铺未绑定';
        }

        $render->pagedata['order_type'] = ($shop[0]['business_type'] == 'zx') ? 'direct' : 'agent';

        $render->pagedata['shop_id'] = $shop_id;
        
        $render->pagedata['config'] = $config;

        return $render->fetch("admin/system/shop_syncorder.html");
    }

    /*
    function detail_request($shop_id) {

        $request_auto_stock = app::get('ome')->getConf('request_auto_stock_' . $shop_id);

        //如无设置,缺省置为 true
        if (empty($request_auto_stock)) {
            $request_auto_stock = 'false';
            app::get('ome')->setConf('request_auto_stock_' . $shop_id, 'true');
        }

        $render = app::get('ome')->render();
        $render->pagedata['request_auto_stock'] = $request_auto_stock;
        $render->pagedata['shop_id'] = $shop_id;
        return $render->fetch("admin/system/terminal_request.html");
    }*/

    function detail_dly_corp($shop_id){
        $shopCropObj = app::get('ome')->model('shop_dly_corp');
        $shopObj = &app::get('ome')->model("shop");
        $shopData = $shopObj->dump($shop_id);
        if($_POST){
            $data['shop_id'] = $_POST['shop_id'];
            if($_POST['config']['cropBind']==1){
                $shopCropObj->delete($data);
                foreach($_POST['crop_name'] as $key=>$crop_name){
                    if($crop_name){
                        $data['corp_id'] = $_POST['corp_id'][$key];
                        $data['crop_name'] = $crop_name;
                        $shopCropObj->save($data);
                    }
                }
            }
            $shopObj->update(array('crop_config'=>$_POST['config']),array('shop_id'=>$_POST['shop_id']));
            $shopData['crop_config'] = $_POST['config'];
        }

        $dlyCropObj = app::get('ome')->model('dly_corp');
        $branchObj = app::get('ome')->model('branch');

        $shopCrop = $shopCropObj->getList('*',array('shop_id'=>$shop_id));
        $shopCrops = array();
        foreach($shopCrop as $key=>$val){
            $shopCrops[$val['corp_id']] = $val['crop_name'];
        }

        $dlyCrops = $dlyCropObj->getList('corp_id, branch_id, name, type, is_cod, weight', array('disabled' => 'false'), 0, -1, 'weight DESC');
        $dlyGroups = array();
        foreach($dlyCrops as $key=>$val){
            $dlyGroups[$val['branch_id']]['dlyCrops'][] = $val;
            $branchIds[$val['branch_id']] = $val['branch_id'];
        }
        $branchs = $branchObj->getList('branch_id, branch_bn, name', array('branch_id' => $branchIds));
        foreach($branchs as $key=>$val){
            $dlyGroups[$val['branch_id']]['name'] = $val['name'];
        }

        $render = app::get('ome')->render();
        $render->pagedata['dlyGroups'] = $dlyGroups;
        $render->pagedata['shopData'] = $shopData;
        $render->pagedata['shopCrops'] = $shopCrops;
        return $render->fetch("admin/system/terminal_dly_crop.html");
    }

    /**
     * 前端店铺订单同步
     * @param String $shop_id 店铺ID
     */
    function detail_shoporderlist($shop_id) {
        $shopObj = &app::get('ome')->model("shop");
        $shopData = $shopObj->dump($shop_id);
        $shop_type = ome_shop_type::shopex_shop_type();
        $config['is_config'] = 'off';
        $config['error_msg'] = '店铺未绑定';
        //店铺显示过滤
        if ($shopData['node_id'] != '') {
            $config['error_msg'] = '本店铺暂不支持同步前端订单';
            if (in_array($shopData['node_type'], $shop_type) !== false) {
                $config['is_config'] = 'on';
            }
        }
        $ayncOrderBns = array();
        if ($config['is_config'] != 'off') {
            $config['error_msg'] = '';
            if (isset($_POST['starttime']) && isset($_POST['endtime'])) {
                $start = explode('-',$_POST['starttime']);
                $end = explode('-', $_POST['endtime']);
                $start_mktime = mktime(0, 0, 0, $start[1], $start[2], $start[0]);
                $end_mktime = mktime(23, 59, 59, $end[1], $end[2], $end[0]);
                $starttime = date('Y-m-d', $start_mktime);
                $endtime = date('Y-m-d', $end_mktime);
            }
            else {
                $start_mktime = time()- 24 * 3600;
                $end_mktime = time();
                $starttime = date('Y-m-d', $start_mktime);
                $endtime = date('Y-m-d', $end_mktime);
            }
            //获取差异数据
            if (isset($_POST['syncorderlist']) && $_POST['syncorderlist'] = '1') {
                $return = kernel::single('apibusiness_router_request')->setShopId($shop_id)->get_order_list($start_mktime, $end_mktime);
                $orderBns = array();
                if ($return['rsp'] == 'success') {
                    foreach ($return['data'] as $data) {
                        $orderBns[] = $data['tid'];
                    }
                }
                $orderObj = &app::get('ome')->model("orders");
                foreach ($orderBns as $order_bn) {
                    $filter = array('shop_id' => $shop_id, 'order_bn' => $order_bn);
                    $result = $orderObj->dump($filter);
                    if (empty($result)) {
                        $ayncOrderBns[] = $order_bn;
                    }
                }
                if (empty($ayncOrderBns)) {
                    $config['error_msg'] = '没有可同步的订单';
                }
            }
        }
        $render = &app::get('ome')->render();
        $render->pagedata['shop_id'] = $shop_id;
        //订单类型
        $render->pagedata['order_type'] = ($shopData['business_type'] == 'zx') ? 'direct' : 'agent';
        $render->pagedata['config'] = $config;
        $render->pagedata['starttime'] = $starttime;
        $render->pagedata['endtime'] = $endtime;
        $render->pagedata['ayncOrderBns'] = $ayncOrderBns;
        return $render->fetch("admin/system/shop_syncorderlist.html");
    }

    var $addon_cols = "shop_id,shop_type,node_id,name,node_type,alipay_authorize";
    var $column_edit = "操作";
    var $column_edit_width = "220";
    function column_edit($row){

        $finder_id = $_GET['_finder']['finder_id'];
        $shop_type = $row[$this->col_prefix.'shop_type'];
        $node_type = $row[$this->col_prefix.'node_type'];
        $node_id = $row[$this->col_prefix.'node_id'];
        $shop_id = $row[$this->col_prefix.'shop_id'];
        $shop_name = $row[$this->col_prefix.'name'];
        $button1 = '<a href="index.php?app=ome&ctl=admin_shop&act=editterminal&p[0]='.$row[$this->col_prefix.'shop_id'].'&finder_id='.$finder_id.'" target="_blank">编辑</a>';
        $taobao_session = &app::get('ome')->getConf('taobao_session_'.$node_id);
        $taobao_session  =  $taobao_session ? $taobao_session : 'false';
        $certi_id = base_certificate::get('certificate_id');
        $api_url = kernel::base_url(true).kernel::url_prefix().'/api';
        $url = OPENID_URL."?open=taobao&certi_id=".$certi_id."&node_id=".$node_id."&refertype=ecos.ome&callback_url=".$api_url;
        
        if ($shop_type=='taobao' and $node_id and ($taobao_session=='false' or $taobao_session=='False' or $taobao_session==false) ){
            //$button2 = ' | <a href="'.$url.'" target="_blank">登录淘宝</a>';
        }
        $callback_url = urlencode(kernel::openapi_url('openapi.ome.shop','shop_callback',array('shop_id'=>$shop_id)));

        $app_id = "ome";
        $api_url = urlencode($api_url);
        if (!$node_id){ 
            $button3 = ' | <a href="index.php?app=ome&ctl=admin_shop&act=apply_bindrelation&p[0]='.$app_id.'&p[1]='.$callback_url.'&p[2]='.$api_url.'" target="_blank">申请绑定</a>';
        }else{
            $button3 = ' | 已绑定';
            if ($row[$this->col_prefix.'alipay_authorize'] == 'true') {
                $button3 .= ' | 支付宝授权'; 
            }
        }

        //扩展操作功能按钮
        if ($extend_button_service = kernel::servicelist('ome.shop.finder')){
            foreach($extend_button_service as $object=>$instance){
                if(method_exists($instance, 'operator_button')){
                    $extend_button .= ' | '.$instance->operator_button($shop_id,$shop_name,$node_id,$node_type);
                }
            }
        }
        if (strlen($extend_button) < '6'){
            $extend_button = '';
        }
        return $button1.$button3.$button2.$extend_button;
    }
}
?>