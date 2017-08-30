<?php
class archive_mdl_delivery extends dbeav_model{
    var $has_many = array(
        'delivery_items' => 'delivery_items',
        'delivery_order' => 'delivery_order',

    );


    static $branchs = array();
    /**
     * 根据订单ID获取发货单.
     * @param   
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_delivery($order_id)
    {
        $deliveryIds  =$this->_get_deliveryId($order_id);

        $deliveryIds_str = implode(',',$deliveryIds);

        $delivery_list = $this->db->select("SELECT * FROM sdb_archive_delivery WHERE delivery_id in(".$deliveryIds_str.")");
        $delivery_items = $this->_get_delivery_items($deliveryIds);

        $delivery_logino = $this->_get_delivery_logino($deliveryIds_str);
        
        foreach ( $delivery_list as $k=>$delivery ) {
            $delivery_list[$k]['items'] = $delivery_items[$delivery['delivery_id']];
            
            $delivery_list[$k]['logino'] = $delivery_logino[$delivery['delivery_id']];
            $delivery_list[$k]['branch_name'] = $this->get_branchname($delivery['branch_id']);
        }
        
        return $delivery_list;

    }

    
    /**
     * 根据order_id
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function _get_deliveryId($order_id)
    {
        $order_delivery = $this->db->select("SELECT * FROM sdb_archive_delivery_order WHERE order_id=".$order_id);
        $ids = array();
        foreach ( $order_delivery as $delivery ) {
            $ids[] = $delivery['delivery_id'];
        }
        return $ids;
    }

    
    /**
     * 发货单明细
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function _get_delivery_items($deliveryIds)
    {
        $deliveryIds_str = implode(',',$deliveryIds);
        $items = $this->db->select("SELECT * FROM sdb_archive_delivery_items WHERE delivery_id in(".$deliveryIds_str.")");
        $item_list = array();
        foreach ($items as $item ) {
            $item_list[$item['delivery_id']][] = $item;
        }
        return $item_list;
    }

    
    /**
     * 获取发货单物流单号
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function _get_delivery_logino($deliveryIds)
    {
        
        $logi_no_list = $this->db->select("SELECT delivery_id,logi_no FROM sdb_archive_delivery_bill WHERE delivery_id in(".$deliveryIds.")");
        $logi_no = array();
        foreach ( $logi_no_list as $logi ) {
            $logi_no[$logi['delivery_id']][] = $logi;
        }
        return $logi_no;
    }

    /*
     * 根据订单id获取发货单信息
     *
     * @param string $cols
     * @param bigint $order_id 订单id
     *
     * @return array $delivery 发货单数组
     */

    function getDeliveryByOrder($cols="*",$order_id){
        $delivery_ids = $this->_get_deliveryId($order_id);
        if($delivery_ids){
            $delivery = $this->getList($cols,array('delivery_id'=>$delivery_ids),0,-1);
            if($delivery){
                foreach($delivery as $k=>$v){
                    if(isset($v['branch_id'])){
                      $branch = $this->db->selectrow("SELECT * FROM sdb_ome_branch WHERE disabled='false' AND branch_id=".intval($v['branch_id']));
                      $delivery[$k]['branch_name'] = $branch['name'];
                    }
                }
                return $delivery;
            }else{
                return array();
            }
        }else{
            return array();
        }
    }
    
    function get_branchname($branch_id){
        if (!self::$branchs[$branch_id]){
            $branch = $this->db->selectrow("SELECT name FROM sdb_ome_branch WHERE branch_id=".$branch_id);
            self::$branchs[$branch_id] = $branch['name'];
        }
        return self::$branchs[$branch_id];
    }

}

?>