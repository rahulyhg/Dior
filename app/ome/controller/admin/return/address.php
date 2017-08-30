<?php
class ome_ctl_admin_return_address extends desktop_controller {

    var $workground = "setting_tools";
   
    function index()
    {
        $params['use_buildin_recycle']=false;
        $params['title'] = '地址库';
        $params['actions'] = array(
                  array(
                    'label' => '同步淘宝地址库',
                    'href' => 'index.php?app=ome&ctl=admin_return_address&act=selectShop',
                    'target' => "dialog::{width:300,height:200,title:'选择店铺'}",
                  ),
           );
        
        $this->finder ( 'ome_mdl_return_address' , $params );
    }

    
    /**
     * 获取地址库
     * @param   
     * @return  
     * @access  public
     * @author cyyr24@sina.cn
     */
    function selectShop()
    {
        $oShop = $this->app->model('shop');
        $shop_list = $oShop->getlist('*',array('shop_type'=>'taobao'));
        if ($shop_list) {
            foreach ($shop_list as $k=>$shop ) {
                if ($shop['node_id'] == '') {
                    unset($shop_list[$k]);
                }
            }
        }
        
        $this->pagedata['shop'] = $shop_list;
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->display('admin/return_product/return_address.html');
    }

    
    /**
     * 获取地址
     * @param   shop_id
     * @return  array
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getAddress()
    {
        $this->begin();
        $shop_id = $_POST['shop_id'];
        if ($shop_id=='') {
            $this->end(false,'请选择店铺');
        }
        $rs = kernel::single('ome_service_aftersale')->searchAddress($shop_id,'');
        $this->end(true,'获取成功');
    }

    /*
     * 通过id获取地址
     */
    function getAddressById(){
        
        $address_id = $_POST['id'];
        if ($address_id){
            $oAddress = $this->app->model('return_address');
            $data = $oAddress->dump(array('address_id'=>$address_id));
            $phone = explode('-',$data['phone']);#将电话处理一下
            $tmp = array(
                'contact_id'    =>$data['contact_id'],
                'address'       =>$data['province'].$data['city '].$data['country'].$data['addr'],
                'zip_code'      =>$data['zip_code'],
                'contact_name'  =>$data['contact_name'],
                'phone'         =>$phone[0].$phone[1],
                'mobile_phone'  =>$data['mobile_phone'],
            );
            echo json_encode($tmp);
            
        }
    }

    function findAddress(){

        $params = array(
            'title'=>'地址列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            
        );
        $this->finder('ome_mdl_return_address', $params);

    }
    
   
}



?>