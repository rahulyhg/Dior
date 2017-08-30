<?php

class omeauto_auto_dispatch {
    
    /**
     * 分派过滤插件
     * 
     * @var Array
     */
    static $_FILTERS = null;
    
    /**
     * 分派规则内容
     * 
     * @var Array
     */
    static $_DISPATCHS = null;
    
    /**
     * 获取系统用户
     * 
     * @param void
     * @return Array
     */
    static function getSystemUser() {
        
        $userInfo = kernel::single('ome_func')->get_system();
        $userInfo['group_id'] = 16777215;
        
        return $userInfo;
    }
    
    /**
     * 获取用户组ID
     * 
     * @param Integer $uid
     * @return Integer
     */
    static function getGroupIdByUId($uid) {
        
        $uid = intval($uid);
        $row = kernel::database()->select("SELECT o.group_id,g.name sdb_ome_groups g left join sdb_ome_group_ops gp ON g.group_id=gp.group_id WHERE g.g_type='confirm' AND gp.op_id={$uid}");
        if (is_array($row) && !empty($row)) {
            
            return $row[0]['group_id'];
        } else {
            
            return 0;
        }
    }
    
    /**
     * 获取当前用户信息
     * 
     * @param void
     * @return Array
     */
    static function getUserInfo() {
        
        $userInfo = kernel::single('ome_func')->getDesktopUser();
        $groupId= self::getGroupIdByUId($userInfo['op_id']);
        $userInf['group_id'] = $groupId;
        
        return $userInfo;
    }
    
    /**
     * 获取指定订单组的自动分配用户信息
     * 
     * @param Array $group 订单数据
     * @return Array
     */
    static function getAutoDispatchUser($group) {

        $result = array('op_id' => 0, 'group_id' => 0);
        
        self::initFilter();
        self::initDispatchRoles();
        foreach (self::$_FILTERS as $tid => $filter) {
            
            if ($filter->vaild($group)) {
                $info = explode('-',$tid);
                if (isset(self::$_DISPATCHS[$info[1]])) {
                    $result['op_id'] = self::$_DISPATCHS[$info[1]]['op_id'];
                    $result['group_id'] = self::$_DISPATCHS[$info[1]]['group_id'];
                }
                
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * 初始化分派规则
     * 
     * @param void
     * @return void
     */
    static function initDispatchRoles() {
        
        if (self::$_DISPATCHS === null) {
            
            self::$_DISPATCHS = array();
            $rows = app::get('omeauto')->model('autodispatch')->getList( '*', array('disabled' => 'false'));
            foreach($rows as $row) {
                
                self::$_DISPATCHS[$row['oid']] = $row;
            } 
        }
    }
    
    /**
     * 初始化插件
     * 
     * @param void
     * @return void
     */
    static function initFilter() {
        
        if (self::$_FILTERS === null) {

            $filters = kernel::single('omeauto_auto_type')->getAutoDispatchTypes();
            self::$_FILTERS = array();
            if ($filters) {

                foreach ($filters as $config) {
                    
                    $filter = new omeauto_auto_group();
                    $filter->setConfig($config);
                    self::$_FILTERS[$config['tid'].'-'.$config['did']] = $filter;
                }
            }
            //增加默认规则,获取系统默认
            $defaultDispatch = app::get('omeauto')->model('autodispatch')->dump(array('defaulted' => 'true', 'disabled'=>'false'));
            if (!empty($defaultDispatch)) {
                $filter = new omeauto_auto_group();
                $filter->setDefault();
                self::$_FILTERS['a-'.$defaultDispatch['oid']] = $filter;
            }
        }
    }
}
