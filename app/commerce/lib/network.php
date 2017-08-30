<?php
class commerce_network{


    
    /**
     * 获取当前绑定状态.
     * @param     
     * @access  public
     * @author 
     */
    function set()
    {
        $netObj = app::get('commerce')->model('network');
        $set_data = $netObj->db->selectrow("select * from sdb_commerce_network WHERE `set`='on'");
        return $set_data;
    }

    
    /**
     * 连接私有矩阵.
     * @param   
     * @return  
     * @access  public
     * @author 
     */
    function conn()
    {
        require_once(ROOT_DIR.'/app/prism/lib/client.php');
        $set  = $this->set();
        $base_url =$set['base_url'];
        $app_key =$set['app_key'];
        $app_secret =$set['app_secret'];
        $http = new prism_client($base_url, $app_key, $app_secret);
        return $http;
    }

   
}

?>