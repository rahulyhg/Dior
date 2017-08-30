<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wms_event_receive_allocate extends wms_event_response{

    /**
     * 调拨操作后变更调拨单的状态
     */
    public function setStatus(){
        return $this->send_succ();
    }

    /**
     * 调拔出库通知单创建事件
     * @param array $data
     */
    public function increate($data){
       
        
        return $this->send_succ();
    }
    /**
     * 调拔入库通知单创建事件
     * @param array $data
     */
    public function outcreate($data){
       
        
        return $this->send_succ();
    }

    public function updateStatus($data){
        return $this->send_succ();
    }
}

?>
