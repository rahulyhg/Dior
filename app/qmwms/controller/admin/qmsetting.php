<?php 
class qmwms_ctl_admin_qmsetting extends desktop_controller{
    public function __construct($app){
        parent::__construct($app);
    }

    public function index(){
        $render = app::get('qmwms')->render();
        $qmwmsApi = app::get('qmwms')->model('qmwms_api');
        $apiData = $qmwmsApi->getList('*',array(),0,1);
        $Data = unserialize($apiData[0]['api_params']);

        $render->pagedata['qmwmsApi'] = $Data;
        $this->page('admin/setting.html');
    }

    public function save(){
        $this->begin('index.php?app=qmwms&ctl=admin_qmsetting&act=index');
        $qmwmsApi = app::get('qmwms')->model('qmwms_api');
        $apiData = $qmwmsApi->getList('*',array(),0,1);

        if($_POST){
            if(!$_POST['app_key']||!$_POST['app_secret']||!$_POST['customerId']|| !$_POST['wms_api']){
                $this->end(false,app::get('base')->_('信息填写不完整'));
            }

            $data['app_key']     = $_POST['app_key'];
            $data['app_secret']  = $_POST['app_secret'];
            $data['customerId']  = $_POST['customerId'];
            $data['wms_api']     = $_POST['wms_api'];
            $data['wms_code']    = $_POST['wms_code'];
            $data['gift_bn']     = $_POST['gift_bn'];
            $data['sample_bn']   = $_POST['sample_bn'];
            $data['mcd_sample_bn']   = $_POST['mcd_sample_bn'];
            $data['mcd_package_sku'] = $_POST['mcd_package_sku'];
            $data['cvd_sample_bn'] = $_POST['cvd_sample_bn'];
            $_data['api_params'] = serialize($data);
            if(empty($apiData)){
                $_data['createtime']    = time();
                $_data['last_modified'] = time();
                $qmwmsApi->insert($_data);
            }else{
                $_data['last_modified'] = time();
                $qmwmsApi->update($_data,array('api_id'=>$apiData[0]['api_id']));
            }

        }
        $this->end(true,app::get('omeftp')->_('保存成功'));
    }


}