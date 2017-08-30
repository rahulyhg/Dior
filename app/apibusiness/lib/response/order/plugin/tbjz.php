<?php
/**
* 淘宝家装插件
*
* @category apibusiness
* @package apibusiness/response/plugin/order
*/
class apibusiness_response_order_plugin_tbjz extends apibusiness_response_order_plugin_abstract
{
    /**
     * 订单完成后处理
     *
     * @return void
     * @author 
     **/
    public function postCreate()
    {
        $order_id = $this->_platform->_newOrder['order_id'];
        $otherlist = json_decode($this->_platform->_ordersdf['other_list'],true);
        
        if ($otherlist) {
            $jzorder_list = array();
            foreach ($otherlist as $other ) {
                if ($other['type'] == 'category') {
                    $jzorder_list[] = $other;
                }
            }
            if (count($jzorder_list)>0) {
                $this->_save_otherlist($order_id,$jzorder_list);
            }
        }
    }

    
     
    /**
     * 保存家装信息.
     * @param  
     * @return  
     * @access  public
     * @author 
     */
    private  function _save_otherlist($order_id,$jzorder_list)
    {
        $jzObj = app::get('ome')->model('tbjz_orders');
        if ($jzorder_list) {

            foreach ($jzorder_list as $order ) {
                $cid = $order['cid'];
                $oid = $order['oid'];
                $jzOrders = $jzObj->dump(array('order_id'=>$order_id,$oid=>$order['oid']));
                if (!$jzOrders) {
                    $jzObj->db->exec("INSERT INTO sdb_ome_tbjz_orders(order_id,cid,oid) VALUE('$order_id','$cid','$oid')");
                }
                
               
            }
        }
    }
}