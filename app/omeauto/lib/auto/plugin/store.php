<?php

/**
 * 检查备注和旗标
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */
class omeauto_auto_plugin_store extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {

    /**
     * 是否支持批量审单
     */
    protected $__SUP_REP_ROLE = true;

    /**
     * 状态码
     */
    protected $__STATE_CODE = omeauto_auto_const::__STORE_CODE;

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {
        $allow = true;
        $groupStore = $this->getGroupStore($group);
        //检查每个订单库存是否充足
        $branchObj = &app::get('ome')->model("branch");
        //根据获取到的仓库来判断库存
        $bids = $group->getBranchId();
        if ($bids) {
            foreach ((array) $bids as $bid ) {
                $delivBranch = $branchObj->getDelivBranch($bid);
                $branchIds = array();
                $branchIds = $delivBranch[$bid]['bind_conf'];
                $branchIds[] = $bid;
                if ($bid > 0) {
                    $sql = "SELECT product_id,sum(IF(store<store_freeze,0,store-store_freeze)) AS store FROM sdb_ome_branch_product WHERE product_id in (".join(',', $groupStore['pids']).") AND branch_id IN (".implode(',', $branchIds).") group by product_id";
                    $prows = kernel::database()->select($sql);
                    //转换数据格式
                    $store = array();
                    foreach ((array) $prows as $row) {
                        $store[$row['product_id']] = $row;
                    }
                    
                    //检查订单组内的货品数量是否足够
                    $allow = true;
                    foreach ($groupStore['store'] as $pid => $nums) {
                        if (($store[$pid]['store'] - $nums) < 0) {
                            
                            $allow = false;
                        } 
                    }
                    
                    if ($allow) {
                        $group->setBranchId($bid);
                        break;
                    }
                } 
                
            }
            if (!$allow) {
                $bids = (array) $bids;
                $group->setBranchId($bids[0]);
            }
        }else{
            $allow = false;
        }
        
        if (!$allow) {
            foreach($group->getOrders() as $order) {
                
                $group->setOrderStatus($order['order_id'], $this->getMsgFlag());
            }
            $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
        }
    }
    
    /**
     * 获取订单组中的所有货品数及货品编号
     * 
     * @param omeauto_auto_group_item $group
     * @return void
     */
    private function getGroupStore($group) {
        
        $result = array('store' => array(), 'pids' => array());
        foreach($group->getOrders() as $order) {
            
            foreach ($order['items'] as $item) {
                
                if (in_array($item['product_id'], $result['pids'])) {
                    //已经存在
                    $result['store'][$item['product_id']] += $item['nums'];
                } else {
                    //没有新的
                    $result['pids'][] = $item['product_id'];
                    $result['store'][$item['product_id']] = $item['nums'];
                }
            }
        }
        
        return $result;
    }

    /**
     * 获取订单中所有的产品IDS
     * 
     * @param Array $order
     * @return Array  
     */
    private function getAllProductIds($order) {

        $ids = array();
        foreach ($order['items'] as $item) {

            $ids[] = $item['product_id'];
        }

        return $ids;
    }

    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '库存不足';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => '#3E3E3E', 'flag' => '库', 'msg' => '库存不足');
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