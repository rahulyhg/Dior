<?php
/**
 * 售后服务类
 *
 *
 **/
class ome_return 
{

    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @description 权限
     * @access public
     * @param void
     * @return void
     */
    public function chkground($workground,$url_params,$permission_id='') 
    {
        static $group;

        if($workground == 'desktop_ctl_recycle') { return true;}
        if($workground == 'desktop_ctl_dashboard') { return true;}
        if($workground == ''){return true;}
        if($_GET['ctl'] == 'adminpanel') return true;
        $menus = app::get('desktop')->model('menus');

        if (!$group) {
            $userLib = kernel::single('desktop_user');
            $group = $userLib->group();
        }
        

        $permission_id = $permission_id ? $permission_id : $menus->permissionId($url_params);
        if($permission_id == '0'){return true;}

        return in_array($permission_id,$group) ? true : false;

    }

    
    /**
     * 生成售后单.
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    public function add($sdf,$shop_id,&$msg="操作失败",&$logTitle="",&$logInfo=""){
       
        $shop = app::get("ome")->model("shop");
        $shop_row = $shop->db->selectrow("select node_id,node_type from sdb_ome_shop where shop_id='".$shop_id."'");
        $log = &app::get('ome')->model('api_log');
        $sdf['node_id'] = $shop_row['node_id'];
        base_rpc_service::$node_id = $sdf['node_id'];
        $rs = kernel::single('apibusiness_router_response')->dispatch('aftersalev2','add',$sdf);
        $data = array('tid'=>$sdf['tid']);
        $rs['rsp'] == 'success';
        $logTitle = $rs['logTitle'];
        $logInfo = $rs['logInfo'];
        $msg = '';
        return true;
        

    }

    function get_return_log($sdf_return,$shop_id,&$msg){
        
        $log = &app::get('ome')->model('api_log');

        $result = $this->add($sdf_return,$shop_id,$msg,$logTitle,$logInfo);

        $class = 'ome_rpc_response_aftersalev2';

        $method = 'add';

        $rsp = 'fail';

        if($result){
            $rsp = 'success';
        }

        return $result;
    }
}