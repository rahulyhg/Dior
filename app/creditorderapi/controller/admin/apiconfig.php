<?php
class creditorderapi_ctl_admin_apiconfig extends desktop_controller{

    public function __construct($app){
        parent::__construct($app);
    }

    public function index(){
        $this->finder('creditorderapi_mdl_apiconfig', array(
            'title' => '多品牌信息配置',
            'actions' => array(
                array('label'=>'添加','href'=>'index.php?app=creditorderapi&ctl=admin_apiconfig&act=create','target'=>'_blank'),
            ),
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => true,
        ));
    }

    function edit(){
        $ax_id = $_GET['ax_id'];
        $apiconfig = app::get('creditorderapi')->model('apiconfig')->getList('*',array('ax_id' => $ax_id));
        $apiconfigInfo = json_decode($apiconfig[0]['ax_setting_info'],1);


        $this->pagedata['apiconfig'] = $apiconfig[0];
        $this->pagedata['apiconfigInfo'] = $apiconfigInfo;
        //绑定店铺信息
        $shopInfo = app::get('ome')->model('shop')->getList('shop_id,name');
        $apiconfig[0]['shop_id'] = unserialize($apiconfig[0]['shop_id']);
        foreach ($shopInfo as $key=>$shop){
            if(in_array($shop['shop_id'],$apiconfig['0']['shop_id'])){
                $shopInfo[$key]['select'] = '1';
            }
        }
        $this->pagedata['shops'] = $shopInfo;

        $this->singlepage('admin/ax/detail.html');
    }

    public function create(){
        $this->pagedata['title'] = '添加品牌AX配置';
        $shopInfo = app::get('ome')->model('shop')->getList('shop_id,name');
        $this->pagedata['shops'] = $shopInfo;
        $this->singlepage('admin/ax/detail.html');
    }

    public function save(){
        $this->begin('index.php?app=creditorderapi&ctl=admin_apiconfig&act=index');
        $apiconfig = app::get('creditorderapi')->model('apiconfig');
        if(!$_POST['shop_id']){
            $this->end(false,app::get('base')->_('请选择店铺信息'));
        }

        if(empty($_POST['ax_id'])){
            $shop_id = $_POST['shop_id'];

            foreach ($shop_id as $shopid){
                $sql = "SELECT * FROM sdb_creditorderapi_apiconfig WHERE shop_id LIKE '%".$shopid."%'";
                $shop = $apiconfig->db->select($sql);
                if(!empty($shop)){
                    $this->end(false,app::get('base')->_('店铺信息已存在!不可以继续添加'));
                }
            }
        }
        $ax_info = array(
            'ax_header' =>$_POST['ax_header'],
            'ax_h' =>$_POST['ax_h'],//2
            'ax_h_sales_country_code' =>$_POST['ax_h_sales_country_code'],
            'ax_h_salas_division' =>$_POST['ax_h_salas_division'],//4
            'ax_h_sales_organization' =>$_POST['ax_h_sales_organization'],
            'ax_h_plant'=>$_POST['ax_h_plant'],//6
            'ax_h_customer_account'=>$_POST['ax_h_customer_account'],
            'ax_h_invoice_ccount'=>$_POST['ax_h_invoice_ccount'],//8
            'ax_h_sales_order_status'=>$_POST['ax_h_sales_order_status'],
            'ax_h_currency'=>$_POST['ax_h_currency'],//10
            'ax_d_mode_of_delivery'=>$_POST['ax_d_mode_of_delivery'],
            'ax_file_brand'=>$_POST['ax_file_brand'],
        );

        $ax_setting_info = json_encode($ax_info);
        $arrAxSet = array(
            'ax_id'=>$_POST['ax_id'],
            'shop_id'=>$_POST['shop_id'],
            'brand_code'=>$_POST['brand_code']?$_POST['brand_code']:'',
            'ax_gift_bn'=>$_POST['ax_gift_bn'],
            'ax_sample_bn'=>$_POST['ax_sample_bn'],
            'ax_file_prefix'=>$_POST['ax_file_prefix'],
            'ax_setting_info'=>$ax_setting_info,
            'secret_key' => $_POST['secret_key'],
            'crm_api_shipurl' => $_POST['crm_api_shipurl'],
            'crm_api_receiveurl' => $_POST['crm_api_receiveurl'],
            'warehouseCode'=>$_POST['warehouseCode'],
            'ownerCode'=>$_POST['ownerCode'],
            'shopNick'=>$_POST['shopNick'],
            'sourcePlatformName'=>$_POST['sourcePlatformName'],
        );

        $this->end($apiconfig->save($arrAxSet),app::get('base')->_('信息保存成功'));
    }

}
