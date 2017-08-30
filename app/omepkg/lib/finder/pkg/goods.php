<?php
class omepkg_finder_pkg_goods{
    var $column_control = '操作';
    var $column_control_width = "150";
    function __construct(){
        $_role = $this->checkedRole('pkg');
        if(!$_role){
            #如果操作人员没有捆绑商品权限，则屏蔽操作这一列，以免没有商品权限的人也有编辑商品的能力
            unset($this->column_control);
        }
    }
    #搜索操作人员是否拥有某个指定权限
    public  function checkedRole($str_role = null){
        $is_super = kernel::single('desktop_user')->is_super();
        #非超级管理员,才进行如下操作
        if(!$is_super){
            #获取网站操作人员id
            $get_id = kernel::single('desktop_user')->get_id();
            #根据操作人员id，获取所有操作角色
            $role = app::get('desktop')->model('hasrole')->getList('role_id',array('user_id'=>$get_id));
            $role_obj = app::get('desktop')->model('roles');
            $_flag = false;
            foreach($role as $v){
                $_workgroud= $role_obj->dump(array('role_id'=>$v),'workground');
                $workgroud = unserialize($_workgroud['workground']);
                #检测角色中是否包含需要搜索的权限
                if(array_search($str_role, $workgroud) !== false){
                    $_flag = true;
                    break;
                }
            }
            return $_flag;
        }else{
            #超级管理员，返回真
            return true;
           }
    }    
    function column_control($row){
        $find_id = $_GET['_finder']['finder_id'];
        return '<a href="index.php?app=omepkg&ctl=admin_pkg&act=edit&p[0]='.$row['goods_id'].'&finder_id='.$find_id.'&_finder[finder_id]='.$find_id.'" target="_blank">编辑</a>';
    }

    var $detail_basic = '捆绑商品详情';
    function detail_basic($pkg_id){
        $render = app::get('omepkg')->render();
        $pkg_product = &app::get('omepkg')->model('pkg_product');
        $data = $pkg_product->getAllProduct($pkg_id);
        $render->pagedata['pkg_data'] = $data;
        return $render->fetch('admin/package/detail_basic.html');
    }

    var $detail_log='操作日志';
    function detail_log($pkg_id) {
        $render = app::get('omepkg')->render();
        $logObj = &app::get('ome')->model('operation_log');
        $pkglog = $logObj->read_log(array('obj_id'=>$pkg_id,'obj_type'=>'pkg_goods@omepkg'), 0, -1);
        foreach($pkglog as $k=>$v){
            $pkglog[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        
        $render->pagedata['pkglog'] = $pkglog;
        return $render->fetch('admin/package/detail_log.html');
    }
    
}
?>
