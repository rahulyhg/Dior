<?php
class ome_batch_order{

    function getBranchStore($order_id,$branch_id)
    {
        $groupStore = array();
        $items = app::get('ome')->model('order_items')->getList('*', array('order_id' => $order_id, 'delete' => 'false'));
        foreach ($items as $item ) {

            if (in_array($item['product_id'], $groupStore['pids'])) {

                $groupStore['store'][$item['product_id']] += $item['nums'];
            } else {
    
                $groupStore['pids'][] = $item['product_id'];
                $groupStore['store'][$item['product_id']] = $item['nums'];
            }
        }
        $sql = "SELECT product_id,sum(IF(store<store_freeze,0,store-store_freeze)) AS store FROM sdb_ome_branch_product WHERE product_id in (".join(',', $groupStore['pids']).") AND branch_id IN (".$branch_id.") group by product_id";
        $prows = kernel::database()->select($sql);
        $store = array();
        foreach ((array) $prows as $row) {
            $store[$row['product_id']] = $row;
        }

        //检查订单组内的货品数量是否足够
        $allow = true;
        foreach ($groupStore['store'] as $pid => $nums) {
            if (($store[$pid]['store'] - $nums) <0) {

                $allow = false;
            } 
        }
        return $allow;
    }

    
    /**
     * 判断到不到
     * @
     * @
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_arrived($order,$corps)
    {
        $arrived_conf = app::get('ome')->getConf('ome.logi.arrived');
        $arrived_auto_conf = app::get('ome')->getConf('ome.logi.arrived.auto');
        $arrivedObj = kernel::single('omeauto_auto_plugin_arrived');
        $allow = true;
        $checkCorp = $arrivedObj->getCheckCorp();
        if ($arrived_conf=='1' && $arrived_auto_conf=='1') {
            $area = $order['ship_area'];
            $addr = $order['ship_addr'];
            $corp_id = $corps['corp_id'];

            $arrivedObj->setAddress($area,$addr);
            $arrivedObj->corp=$corps['type'];

            if (!in_array($arrivedObj->corp,$checkCorp)) {
                return true;
            }
            $result = $arrivedObj->request();

            if(empty($result)) {
                $allow = false;
            }
            if (in_array($result,array('0','2','3'))) {
                $allow = false;
                
            }
        }
        return $allow;
    }


}

?>