<?php
class commerce_ctl_admin_index extends desktop_controller{
    var $workground = "commerce_center";
    public function index(){
         echo MATRIX_URL;
    }    

    public function set(){

        $setObj = $this->app->model('network');
        
        $set_data = $setObj->db->selectrow("select * from sdb_commerce_network");
        
        $this->pagedata['set_data'] = $set_data;
        unset($set_data);
        $this->display('set.html');
    }

    
    /**
     * 保存设置.
     * @
     * @return
     * @access  public
     * @author sunjing@shopex.cn
     */
    function save()
    {
        $this->begin();
        $data = $_POST;

        if ($data['set'] == 'on') {
            if ($data['bind_url'] == '' || $data['license_url'] == '' || $data['callback_url'] == '' || $data['base_url'] == '' || $data['app_key'] == '' || $data['app_secret'] == '') {
                $this->end(false,'开启私有矩阵,绑定相关地址信息不可为空!');
            }
        }
        $setObj = app::get('commerce')->model('network');
        
        $network = $setObj->dump($data['node_id']);
        if ($data['set']!=$network['set']) {
            $shop_list = $setObj->db->select("SELECT * from sdb_ome_shop WHERE node_id!=''");
            if ($shop_list) {
                $this->end(false,'当前店铺绑定关系未解除,请解除后切换');
            }
        }
        $result = $setObj->save($data);
        //更新底层表
        if ($result) {
            $bind_url =MATRIX_URL; 
            //$setObj->db->exec("UPDATE sdb_base_network SET node_url='".$bind_url."'");
        }

        base_certificate::register();
        $this->end(true);
    }


  
}