<?php

/**
 * 检查能否自动确定仓库
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */
class omeauto_auto_plugin_branch extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 状态码
     * 
     * @var Integer
     */
    protected $__STATE_CODE = omeauto_auto_const::__BRANCH_CODE;

    /**
     * 涉及仓库选择的订单分组
     * 
     * @var array
     */
    static $_orderGroups = null;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {

        $branchId = $this->getBranchId($group);
        
        if ($branchId) {
            //设置使用的仓库编号
            $group->setBranchId($branchId);
        } else {
            //设置错误信息
            foreach ($group->getOrders() as $order) {
                $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
            }
            $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
        }
    }

    /**
     * 通过订单组，获取对应仓库
     * 
     * @param omeauto_auto_group_item $group
     * @return void 
     */
    private function getBranchId(&$group) {
        $branchObj = &app::get('ome')->model('branch');
        //$branchs = $branchObj->getList();
        $branchs = $branchObj->db->select("SELECT branch_id,name FROM sdb_ome_branch WHERE is_deliv_branch='true' AND disabled = 'false'");
        if (count($branchs) == 1) {
            return array($branchs[0]['branch_id']);
        }

        $this->initFilters();
        $branch_info = array();
        
        foreach (self::$_orderGroups as $branchId => $filter) {
            
            if ($filter->vaild($group)) {
               
                $info = explode('-',$branchId);
                $branch_info[] = $info[1];
                
            }
        }
        
        return $branch_info;
        
    }

    /**
     * 检查涉及仓库选择的订单分组对像是否已经存在
     * 
     * @param void
     * @return void
     */
    private function initFilters() {

        if (self::$_orderGroups === null) {

            $filters = kernel::single('omeauto_auto_type')->getAutoBranchTypes();
            
            self::$_orderGroups = array();
            if ($filters) {

                foreach ($filters as $config) {
                    
                    $filter = new omeauto_auto_group();
                    $filter->setConfig($config);
                    self::$_orderGroups[$config['tid'].'-'.$config['bid']] = $filter;
                    
                   
                }
            }
            
            //增加缺省订单分组,也就是默认仓库处理
            $defaultBranch = app::get('ome')->model('branch')->dump(array('defaulted' => 'true', 'disabled'=>'false'));
            if (!empty($defaultBranch)) {
                $filter = new omeauto_auto_group();
                $filter->setDefault();
                self::$_orderGroups['a-'.$defaultBranch['branch_id']] = $filter;
               
            }
            
        }
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '无匹配仓库';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => '#48EAED', 'flag' => '仓', 'msg' => '无法对应仓库');
    }

    /**
     * 获取用于快速审核的选项页，输出HTML代码
     * 
     * @param void
     * @return String
     */
    public function getInputUI() {
        
    }

}